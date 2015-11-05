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
use ASM\Session;
use ASM\Serializer\JsonSerializer;

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
     * @param string $path The path to write data and lock files to. This must be a path that
     * supports flock`ing properly i.e. not a NFS path.
     *  
     * @param Serializer $serializer
     * @param IdGenerator $idGenerator
     * @throws AsmException
     */
    public function __construct($path, Serializer $serializer = null, IdGenerator $idGenerator = null)
    {
        if (strlen($path) == 0) {
            throw new AsmException("Empty filepath not acceptable for storing sessions.");
        }

        $this->path = $path;

        if ($serializer) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new JsonSerializer();
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
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return null|string
     */
    public function openSessionByID($sessionId, SessionManager $sessionManager, $userProfile = null)
    {
        $filename = $this->generateFilenameForDataFile($sessionId);
        $fileHandle = @fopen($filename, 'r+');

        if ($fileHandle == false) {
            return null;
        }

        $lockToken = null;
        $isLocked = false;
        $lockFileHandle = null;
        
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $isLocked = true;
            $lockFileHandle = $this->acquireLock(
                $sessionId,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        list($data, $existingProfiles) = $this->read($fileHandle);

        $existingProfiles = $sessionManager->performProfileSecurityCheck(
            $userProfile,
            $existingProfiles
        );
        
        $fileInfo = new FileInfo($fileHandle, $lockFileHandle);

        return new FileSession($sessionId, $data, $this, $sessionManager, $existingProfiles, $fileInfo);
    }

    /**
     * Create a new session.
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
     * @return string The newly created session ID.
     */
    public function createSession(SessionManager $sessionManager, $userProfile = null)
    {
        list($sessionId, $fileHandle) = $this->createNewSessionFile();

        //$filename = $this->generateFilenameForDataFile($sessionId);
        $isLocked = false;
        $lockFileHandle = null;
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $isLocked = true;
            $lockFileHandle = $this->acquireLock(
                $sessionId,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        $existingProfiles = [];
        if ($userProfile !== null) {
            $existingProfiles[] = $userProfile;
        }

        $fileInfo = new FileInfo($fileHandle, $lockFileHandle);
        $this->save($sessionId, [], $existingProfiles,  $fileInfo);
        // fclose($fileHandle); umm.

        return new FileSession($sessionId, [], $this, $sessionManager, $existingProfiles, $fileInfo);
    }

    /**
     * Create a new file safely.
     * 
     * @return array
     * @throws AsmException
     */
    private function createNewSessionFile()
    {
        for ($count=0 ; $count<10 ; $count++) {
            $sessionId = $this->idGenerator->generateSessionID();
            $filename = $this->generateFilenameForDataFile($sessionId);
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
     * Generate a consistent filename for a sessionID
     * 
     * @param $sessionID
     * @internal param $type
     * @return string
     */
    private function generateFilenameForDataFile($sessionID)
    {
        return $this->path.'/'.$sessionID.".data";
    }

    /**
     * Generate a consistent filename for a sessionID
     * 
     * @param $sessionID
     * @internal param $type
     * @return string
     */
    private function generateFilenameForLockFile($sessionID)
    {
        return $this->path.'/'.$sessionID.".lock";
    }
    
    
    /**
     * Saves the data for the sessionID atomically.
     * 
     * @param $sessionId
     * @param $data
     * @param $existingProfiles
     * @param FileInfo $fileInfo
     * @return resource
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    public function save($sessionId, $data, $existingProfiles, FileInfo $fileInfo)
    {
        $rawData = [];
        $rawData['data'] = $data;
        $rawData['profiles'] = $existingProfiles;

        $dataString = $this->serializer->serialize($rawData);
        $filename = $this->generateFilenameForDataFile($sessionId);

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

        if ($fileInfo->lockFileHandle) {
            $this->validateLock($sessionId, $fileInfo);
        }
        else {
            $fileInfo->lockFileHandle = $this->acquireLock(
                $sessionId,
                5000,
                1000
            );  
        }

        //We have the lock - it's safe to replace the datafile
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
        $lockFilename = $this->generateFilenameForLockFile($sessionId);
        $originalInode = null;
        $currentInode = null;
        
        if ($fileInfo->fileHandle == null) {
            return false;
        }

        if ($fileInfo->lockFileHandle == false) {
            return false;
        }

        $originalStat = fstat($fileInfo->fileHandle);
        if(array_key_exists('ino', $originalStat)) {
            $originalInode = $originalStat['ino'];
        }

        $currentStat = @stat($lockFilename);
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
        //TODO - Check if lock has timed out.
        //TODO - do we really want this?

        return true;
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLockByID($sessionID) {
        $filename = $this->generateFilenameForLockFile($sessionID);
        unlink($filename);
    }

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
    public function deleteSessionByID($sessionID)
    {
        $filename = $this->generateFilenameForDataFile($sessionID);
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
    public function acquireLock($sessionId, $lockTimeMS, $acquireTimeoutMS)
    {
        // Get the time in MS
        $currentTimeInMS = (int)(microtime(true) * 1000);
        $giveUpTime = $currentTimeInMS + $acquireTimeoutMS;

        $lockFilename = $this->generateFilenameForLockFile($sessionId); 

        do {
            //Re-open the lock file, to prevent issues where another process
            //deletes it.
            $fileHandle = fopen($lockFilename, 'c+');
            $locked = flock($fileHandle, LOCK_EX|LOCK_NB);
            if ($locked) {
                $touchTime = time() + intval(ceil($lockTimeMS / 1000));
                touch($lockFilename, $touchTime);

                return $fileHandle;
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
    public function renewLock($sessionId, $milliseconds, FileInfo $fileInfo) {
        if (!$this->validateLock($sessionId, $fileInfo)) {
            throw new LostLockException("Cannot renew lock, it has already been released.");
        }
        
        // TODO - there is a race condition here between
        $filename = $this->generateFilenameForDataFile($sessionId);
        $time = time() + intval((ceil($milliseconds / 1000)));
        touch($filename,  $time);
    }

    /**
     * @param FileInfo $fileInfo
     * @return mixed
     */
    public function releaseLock(FileInfo $fileInfo)
    {
        $lockFilehandle = $fileInfo->lockFileHandle;
        $fileInfo->lockFileHandle = null;
        @flock($lockFilehandle, LOCK_UN);
    }

    /**
     * @param FileInfo $fileInfo
     */
    public function close(FileInfo $fileInfo) 
    {
        @fclose($fileInfo->fileHandle);
        $fileInfo->fileHandle = null;

        @fclose($fileInfo->lockFileHandle);
        $fileInfo->lockFileHandle = null;
    }
}