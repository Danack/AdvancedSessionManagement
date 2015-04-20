<?php

namespace ASM\Driver;


use ASM\AsmException;
use ASM\FailedToAcquireLockException;
use ASM\LockAlreadyReleasedException;

class FileDriver implements Driver {

    const LOCK_FILE = 'lock_file';

    const PROFILES_FILE = 'profile_file';
    
    const DATA_FILE = 'data_file';

    private $path;

    /**
     * @var string This is random for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockContents = null;


    protected $fileHandle = false;

    /**
     * @param $path
     * @throws AsmException
     */
    function __construct($path) {
        if (strlen($path) == 0) {
            throw new AsmException("Filepath of '$path' not acceptable for storing sessions.");
        }
        $this->path = $path;
        
        @mkdir($path, true);
    }

    /**
     * 
     */
    function __destruct() {
        if ($this->fileHandle != false) {
            fclose($this->fileHandle);
        }
        $this->fileHandle = null;
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
        $this->fileHandle = @fopen($filename, 'c+'); 

        if ($this->fileHandle === false) {
            throw new AsmException("Failed top open '$filename' to acquire lock.");
        }
        
        $lockRandomNumber = "".rand(100000000, 100000000000);
        // Get the time in MS
        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;
        $finished = false;

        do {
            $wouldBlock = false;
            $locked = flock($this->fileHandle, LOCK_EX|LOCK_NB, $wouldBlock);

            if ($locked) {
                ftruncate($this->fileHandle, 0);
                fwrite($this->fileHandle, $lockRandomNumber);
                break;
            }

            usleep(1000); // sleep 1ms to avoid churning CPU
            
            if ($giveUpTime < ((int)(microtime(true) * 1000))) {
                throw new FailedToAcquireLockException(
                    "FileDriver failed to acquire lock for session $sessionID"
                );
            }
        } while($finished === false);

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
        return (bool)($this->lockContents);
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
    function findSessionIDFromZombieID($sessionID) {
        // TODO: Implement findSessionIDFromZombieID() method.
    }

    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     */
    function setupZombieID($dyingSessionID, $newSessionID, $zombieTimeMilliseconds) {
        // TODO: Implement setupZombieID() method.
    }

    /**
     * @param $sessionID
     * @param $saveData string
     */
    function save($sessionID, $saveData) {
        $filename = $this->generateFilename($sessionID, self::DATA_FILE);
        file_put_contents($filename, $saveData);
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