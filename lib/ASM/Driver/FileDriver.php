<?php

namespace ASM\Driver;

use ASM\Serializer;
use ASM\IDGenerator;

use ASM\AsmException;
use ASM\FailedToAcquireLockException;
use ASM\LockAlreadyReleasedException;

class FileDriver implements Driver {

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
     * @var string This is random for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockContents = null;

    /**
     * @var bool
     */
    protected $fileHandle = null;

    /**
     * @param $path
     * @param Serializer $serializer
     * @param IDGenerator $idGenerator
     * @throws AsmException
     */
    function __construct($path, Serializer $serializer = null, IDGenerator $idGenerator = null) {
        if (strlen($path) == 0) {
            throw new AsmException("Filepath of '$path' not acceptable for storing sessions.");
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
     * 
     */
    function __destruct() {
        if ($this->fileHandle != null) {
            fclose($this->fileHandle);
        }
        $this->fileHandle = null;
    }
    
    
    function close() {
        //releaseLock
    //    $this->__destruct();
    }

    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionID
     * @return string|null
     */
    function openSession($sessionID) {
        $filename = $this->generateFilename($sessionID, self::DATA_FILE);
        $fileContents = @file_get_contents($filename);
        if ($fileContents === false) {
            return false;
        }
        
        return $this->serializer->unserialize($fileContents);
    }

    /**
     * @return array
     * @throws AsmException
     */
    private function createNewSessionFile() {
        $count = 10;
        do {
            $sessionID = $this->idGenerator->generateSessionID();
            $filename = $this->generateFilename($sessionID, self::DATA_FILE);
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
     * Create a new session.
     * @return string The newly created session ID.
     */
    function createSession() {
        list($sessionID, $fileHandle) = $this->createNewSessionFile();
        $dataString = $this->serializer->serialize([]);
        fwrite($fileHandle, $dataString);
        $this->fileHandle = $fileHandle;

        return $sessionID;
    }


    /**
     * @param $sessionID
     * @param $type
     * @return string
     * @throws AsmException
     */
    private function generateFilename($sessionID, $type) {
        $basename = $this->path.'/'.$sessionID;
        
        switch ($type) {
            case(self::LOCK_FILE) : {
                return $basename.".lock";
            }

            case(self::PROFILES_FILE) : {
                return $basename.".profiles";
            }
            
            case(self::DATA_FILE) : {
                return $basename.".data";
            }

            case(self::ZOMBIE_FILE): {
                return $basename.".zombie";
            }
        }

        throw new AsmException("Unknown session file type [$type]");
    }
    
    /**
     * Acquire a lock for the session
     * @param $sessionID
     * @param $milliseconds
     */
    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS) {
        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
        // Open the file for reading + writing . If the file does not exist,
        // it is created. If it exists, it is neither truncated (as opposed
        // to 'w'), nor the call to this function fails (as is the case with 'x').
        $fileHandle = @fopen($filename, 'c+'); 

        if ($fileHandle === false) {
            throw new AsmException("Failed top open '$filename' to acquire lock.");
        }
        
        $lockRandomNumber = "".rand(100000000, 100000000000);
        // Get the time in MS
        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;
        $finished = false;

        do {
            $wouldBlock = false;
            $locked = flock($fileHandle, LOCK_EX|LOCK_NB, $wouldBlock);

            if ($locked) {
                ftruncate($fileHandle, 0);
                fwrite($fileHandle, $lockRandomNumber);
                break;
            }

            usleep(1000); // sleep 1ms to avoid churning CPU

            if ($giveUpTime < ((int)(microtime(true) * 1000))) {
                fclose($fileHandle);
                throw new FailedToAcquireLockException(
                    "FileDriver failed to acquire lock for session $sessionID"
                );
            }
        } while($finished === false);

        $this->fileHandle = $fileHandle;
        $this->lockContents = $lockRandomNumber;
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
        
        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
        $time = time() + ((int)(($milliseconds + 999) / 1000));
        touch($filename,  $time);
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function releaseLock($sessionID) {
        $result = true;
        
        if ($this->validateLock($sessionID) == false) {
            $result = false;
            fseek($this->fileHandle, 0);
            ftruncate($this->fileHandle, 0);
        }

        flock($this->fileHandle, LOCK_UN);
        fclose($this->fileHandle);
        $this->fileHandle = null;

        return $result;
    }

    /**
     * Test whether the driver thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     * @param $sessionID
     * @return boolean
     */
    function isLocked($sessionID) {
        return (bool)($this->fileHandle);
    }

    /**
     * @param $sessionID
     * @return boolean
     */
    function validateLock($sessionID) {
        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
        $contents = @file_get_contents($filename);
        return (bool)($this->lockContents === $contents);
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLock($sessionID) {
        $filename = $this->generateFilename($sessionID, self::LOCK_FILE);
        unlink($filename);
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function findSessionIDFromZombieID($zombieSessionID) {
        $filename = $this->generateFilename($zombieSessionID, self::ZOMBIE_FILE);
        $fileContents = @file_get_contents($filename);

        return $fileContents;
    }

    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     */
    function setupZombieID($dyingSessionID, $zombieTimeMilliseconds) {
        // TODO - this needs to be wrapped in a lock.
        list($newSessionID, $newFileHandle) = $this->createNewSessionFile();
        
        fseek($this->fileHandle, 0);
        stream_copy_to_stream($this->fileHandle, $newFileHandle);

        $zombieFilename = $this->generateFilename($dyingSessionID, self::ZOMBIE_FILE);
        file_put_contents($zombieFilename, "".$newSessionID."");

        //TODO - rename profile 
        
        
        // Store as temp variable so that $this->fileHandle is always a file 
        // handle to a valid file.
        $oldFileHandle = $this->fileHandle;
        $this->fileHandle = $newFileHandle;
        fclose($oldFileHandle);

        return $newSessionID;
    }

    /**
     * @param $sessionID
     * @param $saveData string
     */
    function save($sessionID, $saveData) {
        $filename = $this->generateFilename($sessionID, self::DATA_FILE);
        
        $dataString = $this->serializer->serialize($saveData);
        
        //TODO - This needs to be made atomic with rename?
        file_put_contents($filename, $dataString);
    }


    /**
     * @return mixed
     */
    function destroyExpiredSessions() {
        // TODO: Implement destroyExpiredSessions() method.
    }

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID) {
        $filename = $this->generateFilename($sessionID, self::DATA_FILE);
        unlink($filename);
    }

    /**
     * @param $sessionID
     * @param $sessionProfile
     */
    function addProfile($sessionID, $sessionProfile) {
        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
        $fileHandle = @fopen($filename, 'a');
        if ($fileHandle === false) {
            throw new AsmException("Failed to open profile file '$filename' to append.");
        }
        fwrite($fileHandle, $sessionProfile."\n");
        @fclose($fileHandle);
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function getStoredProfile($sessionID) {
        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
        $filelines = @file($filename, FILE_IGNORE_NEW_LINES);
        if ($filelines === false) {
            return [];
        }

        return $filelines;
    }

    /**
     * @param $sessionID
     * @param array $sessionProfiles
     */
    function storeSessionProfiles($sessionID, array $sessionProfiles) {
        $filename = $this->generateFilename($sessionID, self::PROFILES_FILE);
        $contents = implode("\n", $sessionProfiles);
        file_put_contents($filename, $contents);
    }
}      