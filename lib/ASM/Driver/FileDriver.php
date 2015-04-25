<?php

namespace ASM\Driver;

use ASM\Serializer;
use ASM\IDGenerator;

use ASM\AsmException;


class FileDriver implements Driver
{

    const LOCK_FILE = 'lock_file';

    const PROFILES_FILE = 'profile_file';

    const DATA_FILE = 'data_file';

    const ZOMBIE_FILE = 'zombie_file';

    private $path;

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
    function __construct($path, Serializer $serializer = null, IDGenerator $idGenerator = null)
    {
        if (strlen($path) == 0) {
            throw new AsmException("Empty filepath not acceptable for storing sessions.");
        }

        $this->path = $path;

        if ($serializer) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new \ASM\PHPSerializer();
        }

        if ($idGenerator) {
            $this->idGenerator = $idGenerator;
        }
        else {
            $this->idGenerator = new \ASM\StandardIDGenerator();
        }
    }

    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionID
     * @return string|null
     */
    function openSession($sessionID)
    {
        $filename = $this->generateFilenameForData($sessionID);
        if (file_exists($filename) == false) {
            return null;
        }

        return new FileOpenSession($sessionID, $this);
    }

    /**
     * Create a new session.
     * @return string The newly created session ID.
     */
    function createSession()
    {
        list($sessionID, $fileHandle) = $this->createNewSessionFile();
        $dataString = $this->serializer->serialize([]);
        fwrite($fileHandle, $dataString);

        return new FileOpenSession($sessionID, $this);
    }

    /**
     * @return array
     * @throws AsmException
     */
    private function createNewSessionFile()
    {
        $count = 10;
        do {
            $sessionID = $this->idGenerator->generateSessionID();
            $filename = $this->generateFilenameForData($sessionID);
            //This only succeeds if the file doesn't already exist
            $fileHandle = @fopen($filename, 'x+');

            if ($fileHandle != false) {
                break;
            }

            $count++;
            if ($count > 10) {
                //TODO - improve conditions of when an exception is thrown
                throw new AsmException("Failed to open a new session file.");
            }
        } while (1);

        return [$sessionID, $fileHandle];
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
     * @param $sessionID
     * @param $saveData
     * @throws AsmException
     */
    function save($sessionID, $saveData)
    {
        $filename = $this->generateFilenameForData($sessionID);
        $dataString = $this->serializer->serialize($saveData);
        file_put_contents($filename, $dataString);
    }


    /**
     * @param $sessionID
     * @return mixed
     * @throws AsmException
     */
    function read($sessionID)
    {
        $filename = $this->generateFilenameForData($sessionID);
        $dataString = file_get_contents($filename);
        $data = $this->serializer->unserialize($dataString);

        return $data;
    }


//    
//    /**
//     * Acquire a lock for the session
//     * @param $sessionID
//     * @param $milliseconds
//     */
//    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS) {
//        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
//        // Open the file for reading + writing . If the file does not exist,
//        // it is created. If it exists, it is neither truncated (as opposed
//        // to 'w'), nor the call to this function fails (as is the case with 'x').
//        $fileHandle = @fopen($filename, 'c+'); 
//
//        if ($fileHandle === false) {
//            throw new AsmException("Failed top open '$filename' to acquire lock.");
//        }
//        
//        $lockRandomNumber = "".rand(100000000, 100000000000);
//        // Get the time in MS
//        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;
//        $finished = false;
//
//        do {
//            $wouldBlock = false;
//            $locked = flock($fileHandle, LOCK_EX|LOCK_NB, $wouldBlock);
//
//            if ($locked) {
//                ftruncate($fileHandle, 0);
//                fwrite($fileHandle, $lockRandomNumber);
//                break;
//            }
//
//            usleep(1000); // sleep 1ms to avoid churning CPU
//
//            if ($giveUpTime < ((int)(microtime(true) * 1000))) {
//                fclose($fileHandle);
//                throw new FailedToAcquireLockException(
//                    "FileDriver failed to acquire lock for session $sessionID"
//                );
//            }
//        } while($finished === false);
//
//        $this->fileHandle = $fileHandle;
//        $this->lockContents = $lockRandomNumber;
//    }
//
//    /**
//     * @param $sessionID
//     * @param $milliseconds
//     * @return mixed
//     */
//    function renewLock($sessionID, $milliseconds) {
//        if (!$this->validateLock($sessionID)) {
//            throw new LockAlreadyReleasedException("Cannot renew lock, it has already been released.");
//        }
//        
//        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
//        $time = time() + ((int)(($milliseconds + 999) / 1000));
//        touch($filename,  $time);
//    }
//
//    /**
//     * @param $sessionID
//     * @return mixed
//     */
//    function releaseLock($sessionID) {
//        $result = true;
//        
//        if ($this->validateLock($sessionID) == false) {
//            $result = false;
//            fseek($this->fileHandle, 0);
//            ftruncate($this->fileHandle, 0);
//        }
//
//        flock($this->fileHandle, LOCK_UN);
//        fclose($this->fileHandle);
//        $this->fileHandle = null;
//
//        return $result;
//    }
//
//    /**
//     * Test whether the driver thinks the data is locked. The result may
//     * not be accurate when another process has force released the lock.
//     * @param $sessionID
//     * @return boolean
//     */
//    function isLocked($sessionID) {
//        return (bool)($this->fileHandle);
//    }
//
//    /**
//     * @param $sessionID
//     * @return boolean
//     */
//    function validateLock($sessionID) {
//        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
//        $contents = @file_get_contents($filename);
//        return (bool)($this->lockContents === $contents);
//    }
//
//    /**
//     * @param $sessionID
//     * @return mixed
//     */
//    function forceReleaseLock($sessionID) {
//        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
//        unlink($filename);
//    }
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
//
    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID)
    {
        $filename = $this->generateFilenameForData($sessionID);
        unlink($filename);
    }
//
//    /**
//     * @param $sessionID
//     * @param $sessionProfile
//     */
//    function addProfile($sessionID, $sessionProfile) {
//        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
//        $fileHandle = @fopen($filename, 'a');
//        if ($fileHandle === false) {
//            throw new AsmException("Failed to open profile file '$filename' to append.");
//        }
//        fwrite($fileHandle, $sessionProfile."\n");
//        @fclose($fileHandle);
//    }
//
//    /**
//     * @param $sessionID
//     * @return mixed
//     */
//    function getStoredProfile($sessionID) {
//        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
//        $filelines = @file($filename, FILE_IGNORE_NEW_LINES);
//        if ($filelines === false) {
//            return [];
//        }
//
//        return $filelines;
//    }
//
//    /**
//     * @param $sessionID
//     * @param array $sessionProfiles
//     */
//    function storeSessionProfiles($sessionID, array $sessionProfiles) {
//        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
//        $contents = implode("\n", $sessionProfiles);
//        file_put_contents($filename, $contents);
//    }
}      