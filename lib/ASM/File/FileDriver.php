<?php

namespace ASM\File;

use ASM\Driver;
use ASM\FailedToAcquireLockException;
use ASM\Serializer;
use ASM\IdGenerator;
use ASM\AsmException;
use ASM\SessionManager;
use ASM\SessionConfig;


class FileDriver implements Driver
{

    const DATA_FILE = 'data_file';

    const ZOMBIE_FILE = 'zombie_file';

    private $path;
    
    private $data;

    /**
     * @var Serializer
     */
    private $serializer;


    /**
     * @var IDGenerator
     */
    private $idGenerator;


    /**
     * @param $path
     * @param Serializer $serializer
     * @param IDGenerator $idGenerator
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
            $lockToken = $this->acquireLock(
                $fileHandle,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        $data = $this->read($fileHandle);
        
        $fileInfo = new FileInfo($fileHandle, $lockToken, $isLocked);

        return new FileSession($sessionId, $data, $this, $sessionManager, $userProfile, $fileInfo);
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

        $isLocked = false;
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $isLocked = true;
            $this->acquireLock(
                $fileHandle, 
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

//    private function openFile($sessionId, SessionManager $sessionManager, $mode)
//    {
//        //$fileHandleAndLockToken = null;
////        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
////            return $this->acquireLock(
////                $sessionId,
////                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
////                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
////            );
////        }
//
//        $filename = $this->generateFilenameForData($sessionId);
//        // Open the file for reading + writing . If the file does not exist,
//        // it is created. If it exists, it is neither truncated (as opposed
//        // to 'w'), nor the call to this function fails (as is the case with 'x').
//        return @fopen($filename, $mode);
//        
////        if ($fileHandle) {
////            return new FileInfo($fileHandle, null, false);
////        }
//
////        return null;
//    }
    
    
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
        //$rawData['lockToken'] = $fileInfo->lockToken;

        $dataString = $this->serializer->serialize($rawData);
        $filename = $this->generateFilenameForData($sessionId);

        $tempFilename = tempnam(dirname($filename), basename($filename));
        $writeResult = @file_put_contents($filename, $dataString);
        if ($writeResult === false) {
            throw new AsmException("Failed to write session data.");
        }

        $newFileHandle = fopen($tempFilename, 'r+');
        $dataWritten = fwrite($newFileHandle, $dataString);
        
        if ($dataWritten === false) {
            throw new AsmException("Failed to save session data writing of data.");
        }

        if ($fileInfo->isLocked) {
            $this->acquireLock(
                $newFileHandle,
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
     * @param $sessionID
     * @return boolean
     */
    function validateLock($sessionID)
    {
        $filename = $this->generateFilenameForLock($sessionID, self::LOCK_FILE);
        $contents = @file_get_contents($filename);
        return (bool)($this->lockContents === $contents);
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLock($sessionID) {
        $filename = $this->generateFilenameForLock($sessionID);
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
     * @param $sessionId
     * @param $milliseconds
     */
    function acquireLock($fileHandle, $lockTimeMS, $acquireTimeoutMS)
    {
//        $filename = $this->generateFilenameForLock($sessionId);
//        // Open the file for reading + writing . If the file does not exist,
//        // it is created. If it exists, it is neither truncated (as opposed
//        // to 'w'), nor the call to this function fails (as is the case with 'x').
//        $fileHandle = @fopen($filename, 'c+');
//
//        if ($fileHandle === false) {
//            throw new AsmException("Failed top open '$filename' to acquire lock.");
//        }

        //$lockToken = $this->idGenerator->generateSessionID();
        // Get the time in MS
        $currentTimeInMS = (int)(microtime(true) * 1000);
        
        $giveUpTime = $currentTimeInMS + $acquireTimeoutMS;
        //$lockExpireTime = $currentTimeInMS + $lockTimeMS;

        do {
            //$wouldBlock = false;
            $locked = flock($fileHandle, LOCK_EX|LOCK_NB);//, $wouldBlock);
            if ($locked) {
//                ftruncate($fileHandle, 0);
//                $dataString = json_encode([
//                    'expireTime' => $lockExpireTime,
//                    'token' => $lockToken,
//                ]);
//
//                fwrite($fileHandle, $dataString);

//                touch ( string $filename [, int $time = time() [, int $atime ]] )
                
                
                return true;
            }
            usleep(1000); // sleep 1ms to avoid churning CPU
        } while($giveUpTime > ((int)(microtime(true) * 1000)));

        //fclose($fileHandle);

        throw new FailedToAcquireLockException(
            "FileDriver failed to acquire lock."
        );
    }

    /**
     * @param $sessionID
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($sessionID, $milliseconds) {
        if (!$this->validateLock($sessionID)) {
            throw new LockAlreadyReleasedException("Cannot renew lock, it has already been released.");
        }

        $filename = $this->generateFilenameForLock($sessionID);
        $time = time() + ((int)(($milliseconds + 999) / 1000));
        touch($filename,  $time);
    }

    /**
     * @param $sessionId
     * @return mixed
     */
    function releaseLock($sessionId, FileInfo $fileInfo) {
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