<?php

namespace ASM\File;

use ASM\Driver;
use ASM\FailedToAcquireLockException;
use ASM\Serializer;
use ASM\IdGenerator;
use ASM\AsmException;
use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\LostLockException;

class FileDriver implements Driver
{

    const DATA_FILE = 'data_file';

    const ZOMBIE_FILE = 'zombie_file';

    private $path;


    /**
     * @var Serializer
     */
    private $serializer;


    /**
     * @var IdGenerator
     */
    private $idGenerator;


    /**
     * @param $path
     * @param Serializer $serializer
     * @param IdGenerator $idGenerator
     * @throws AsmException
     */
    function __construct($path, Serializer $serializer = null, IdGenerator $idGenerator = null)
    {
        if (strlen($path) == 0) {
            throw new AsmException("Empty filepath not acceptable for storing sessions.");
        }

        $this->path = $path;

        if ($serializer) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new \ASM\Serializer\PHPSerializer();
        }

        if ($idGenerator) {
            $this->idGenerator = $idGenerator;
        }
        else {
            $this->idGenerator = new \ASM\IdGenerator\RandomLibIdGenerator();
        }
    }

    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionId
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return null|string
     */
    function openSession($sessionId, SessionManager $sessionManager, $userProfile = null)
    {
        $filename = $this->generateFilenameForData($sessionId);
        $fileHandle = @fopen($filename, 'r+');

        if ($fileHandle == false) {
            return null;
        }

        $lockToken = null;
        $isLocked = false;
        
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $isLocked = true;
            $this->acquireLock(
                $fileHandle,
                $filename,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        list($data, $existingProfiles) = $this->read($fileHandle);

        $existingProfiles = $sessionManager->performProfileSecurityCheck(
            $userProfile,
            $existingProfiles
        );
        
        $fileInfo = new FileInfo($fileHandle, $isLocked);

        return new FileSession($sessionId, $data, $this, $sessionManager, $existingProfiles, $fileInfo);
    }

    /**
     * Create a new session.
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
     * @return string The newly created session ID.
     */
    function createSession(SessionManager $sessionManager, $userProfile = null)
    {
        list($sessionId, $fileHandle) = $this->createNewSessionFile();

        $filename = $this->generateFilenameForData($sessionId);

        $isLocked = false;
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $isLocked = true;
            $this->acquireLock(
                $fileHandle,
                $filename,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        $existingProfiles = [];
        if ($userProfile !== null) {
            $existingProfiles[] = $userProfile;
        }

        $fileInfo = new FileInfo($fileHandle, $isLocked);
        $this->save($sessionId, [], $existingProfiles,  $fileInfo);
        fclose($fileHandle);

        return new FileSession($sessionId, [], $this, $sessionManager, $existingProfiles, $fileInfo);
    }

    /**
     * @return array
     * @throws AsmException
     */
    private function createNewSessionFile()
    {
        for ($count=0 ; $count<10 ; $count++) {
            $sessionId = $this->idGenerator->generateSessionID();
            $filename = $this->generateFilenameForData($sessionId);
            //TODO remove? - the user should create the directory themselves
            @mkdir(dirname($filename));
            //This only succeeds if the file doesn't already exist
            $fileHandle = @fopen($filename, 'x+');
            if ($fileHandle != false) {
                return [$sessionId, $fileHandle];
            }
        };

        throw new AsmException("Failed to open a new session file with random name.");
    }

    /**
     * @param $sessionID
     * @internal param $type
     * @return string
     */
    private function generateFilenameForData($sessionID)
    {
        return $this->path.'/'.$sessionID.".data";
    }

    /**
     * @param $sessionId
     * @param $data
     * @param $existingProfiles
     * @param FileInfo $fileInfo
     * @return resource
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    function save($sessionId, $data, $existingProfiles, FileInfo $fileInfo)
    {
        $rawData = [];
        $rawData['data'] = $data;
        $rawData['profiles'] = $existingProfiles;

        $dataString = $this->serializer->serialize($rawData);
        $filename = $this->generateFilenameForData($sessionId);

        $tempFilename = tempnam(dirname($filename), basename($filename));
        $writeResult = @file_put_contents($filename, $dataString);
        if ($writeResult === false) {
            throw new AsmException("Failed to write session data.");
        }

        $newFileHandle = @fopen($tempFilename, 'r+');
        if ($newFileHandle === false) {
            throw new AsmException("Failed to open $tempFilename.");
        }
        
        $dataWritten = fwrite($newFileHandle, $dataString);
        
        if ($dataWritten === false) {
            throw new AsmException("Failed to save session data writing of data.");
        }

        if ($fileInfo->isLocked) {
            $this->acquireLock(
                $newFileHandle,
                $filename,
                5000,
                1000
            );
        }

        $renamed = rename($tempFilename, $filename);

        if (!$renamed) {
            throw new AsmException("Failed to save session data during rename of file");
        }

        $fileInfo->fileHandle = $newFileHandle; 
    }


    /**
     * @param $sessionID
     * @return mixed
     * @throws AsmException
     */
    function read($fileHandle)
    {
        $dataString = stream_get_contents($fileHandle);
        //TODO - should we catch unserialization errors?
        $rawData = $this->serializer->unserialize($dataString);

        $data = null;
        $existingProfiles = null;
        $lockToken = null;
        
        if (array_key_exists('data', $rawData)) {
            $data = $rawData['data'];
        }
        if (array_key_exists('profiles', $rawData)) {
            $existingProfiles = $rawData['profiles'];
        }
//        if (array_key_exists('lockToken', $rawData)) {
//            $lockToken = $rawData['lockToken'];
//        }

        return [$data, $existingProfiles];
    }

    /**
     * Test whether the driver thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     * @param $sessionID
     * @return boolean
     */
//    function isLocked($sessionID) {
//        return (bool)($this->fileHandle);
//    }

    /**
     * @param $sessionId
     * @return boolean
     */
    function validateLock($sessionId, FileInfo $fileInfo)
    {
        $filename = $this->generateFilenameForData($sessionId);
        $originalInode = null;
        $currentInode = null;
        
        if ($fileInfo->fileHandle == null) {
            return false;
        }

        if ($fileInfo->isLocked == false) {
            return false;
        }

        $originalStat = fstat($fileInfo->fileHandle);
        if(array_key_exists('ino', $originalStat)) {
            $originalInode = $originalStat['ino'];
        }

        $currentStat = @stat($filename);
        if($currentStat && array_key_exists('ino', $currentStat)) {
            $currentInode = $currentStat['ino'];
        }

        if ($currentInode == null) {
            return false;
        }
        else if ($originalInode == null) {
            return false;
        }
        else if ($currentInode != $originalInode) {
            return false;
        }

        if (array_key_exists('mtime', $currentStat) == false) {
            throw new AsmException("Cannot validate lock mtime is not valid.");
        }
        
        $now = microtime();
         
        

        return true;
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLock($sessionID) {
        $filename = $this->generateFilenameForData($sessionID);
        unlink($filename);
    }
//
//    /**
//     * @param $sessionID
//     * @return mixed
//     */
//    function findSessionIDFromZombieID($zombieSessionID) {
//        $filename = $this->generateFilename($zombieSessionID, self::ZOMBIE_FILE);
//        $fileContents = @file_get_contents($filename);
//
//        return $fileContents;
//    }
//
//    /**
//     * @param $dyingSessionID
//     * @param $newSessionID
//     * @param $zombieTimeMilliseconds
//     */
//    function setupZombieID($dyingSessionID, $zombieTimeMilliseconds) {
//        // TODO - this needs to be wrapped in a lock.
//        list($newSessionID, $newFileHandle) = $this->createNewSessionFile();
//        
//        fseek($this->fileHandle, 0);
//        stream_copy_to_stream($this->fileHandle, $newFileHandle);
//
//        $zombieFilename = $this->generateFilename($dyingSessionID, self::ZOMBIE_FILE);
//        file_put_contents($zombieFilename, "".$newSessionID."");
//
//        //TODO - rename profile 
//        
//        
//        // Store as temp variable so that $this->fileHandle is always a file 
//        // handle to a valid file.
//        $oldFileHandle = $this->fileHandle;
//        $this->fileHandle = $newFileHandle;
//        fclose($oldFileHandle);
//
//        return $newSessionID;
//    }
//
//
//
//    /**
//     * @return mixed
//     */
//    function destroyExpiredSessions() {
//        // TODO: Implement destroyExpiredSessions() method.
//    }

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID)
    {
        $filename = $this->generateFilenameForData($sessionID);
        unlink($filename);
    }

    /**
     * Acquire a lock for the session
     * @param $fileHandle
     * @param $filename
     * @param $lockTimeMS
     * @param $acquireTimeoutMS
     * @return bool
     * @throws FailedToAcquireLockException
     */
    function acquireLock($fileHandle, $filename, $lockTimeMS, $acquireTimeoutMS)
    {
        // Get the time in MS
        $currentTimeInMS = (int)(microtime(true) * 1000);
        $giveUpTime = $currentTimeInMS + $acquireTimeoutMS;

        do {
            //$wouldBlock = false;
            $locked = flock($fileHandle, LOCK_EX|LOCK_NB);
            if ($locked) {
                $touchTime = time() + intval(ceil($lockTimeMS / 1000));
                touch($filename, $touchTime);

                return true;
            }
            usleep(1000); // sleep 1ms to avoid churning CPU
        } while($giveUpTime > ((int)(microtime(true) * 1000)));

        throw new FailedToAcquireLockException(
            "FileDriver failed to acquire lock."
        );
    }

    /**
     * @param $sessionId
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($sessionId, $milliseconds, FileInfo $fileInfo) {
        if (!$this->validateLock($sessionId, $fileInfo)) {
            throw new LostLockException("Cannot renew lock, it has already been released.");
        }
        
        // TODO - there is a race condition here between
        $filename = $this->generateFilenameForData($sessionId);
        $time = time() + intval((ceil($milliseconds / 1000)));
        touch($filename,  $time);
    }

    /**
     * @param FileInfo $fileInfo
     * @return mixed
     */
    function releaseLock(FileInfo $fileInfo) {
        @flock($fileInfo->fileHandle, LOCK_UN);
        $fileInfo->isLocked = false;
    }

    function close(FileInfo $fileInfo) 
    {
        @fclose($fileInfo->fileHandle);
        $fileInfo->fileHandle = null;
        $fileInfo->isLocked = false;   
    }
}