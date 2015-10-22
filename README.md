
# DO NOT USE

It's not working correctly yet...

# Advanced session management

## Terminology

### UserProfile

A UserProfile is a string that holds some information about the computer that is accessing the session. For example the computers IP address and useragent.

When the session is accessed, the user-profile for the current request is checked against the user-profiles that have already been used to access the session. If they are not identical, the profileChanged callable is called.
 
This can be used to detect and prevent an attacker from being able to access the session, even if they know the session ID. The implementation of the profileChanged callable must be supplied by the programmer who is using this library.

For websites that are just showing pictures of funny cats, this security check could be very lax, or completely missing.

For banks or other websites where security is paramount



### Zombie Session

[Session fixation](https://www.owasp.org/index.php/Session_fixation) is an attack where a 3rd party manages to figure out someone's session id, and can spoof.
 
These attacks can be limited by using Session::regenerateSessionID, which generates a new session ID for the legitimate user. However that causes a problem when multiple requests arrive in a short amount of time.
 
For example, a user browses to your website, opens 3 tabs pointing to different pages.  


### Driver

ASM can use several backend storage systems, the code that provide hese are called 'drivers'. Currently, the Redis and filesystem drivers have been implemented. Pull requests for drivers for other storage systems are very welcome.


### Locking


* Lock on open - 

* Lock on write - 



## Callbacks


### Profile changed


function profileChanged(\Asm\Session $session, $newProfile, $previousProfiles) {
    if (isProfileChangeAllowed($newProfile, $previousProfiles) == false) {
        throw new UserDefinedException("Profile is too different.");
    }

    $previousProfiles[] = $newProfile;
    
    return $previousProfiles;
}

### Zombie key accessed

Called when a user attempts to use a session ID that is actually now a zombie ID. 

function zombieKeyAccessed(\Asm\Session $session) {

}

### Invalid session accessed

Called when a user attempts to use a session ID that is invalid. This would be useful for preventing flood attacks where someone is making a large number of requests in an attempt to guess a session ID

function invalidSessionAccessed(\Asm\Session $session) {

}

### Session Lost Lock 

In some circumstances the lock on the session data can be lost.

function lostLockCallable(\Asm\Session $session) {

}
















## Goals


### Explicit locking

Be explicit and expose locking similar to how databases expose different levels of locking, and allow applications select the appropriate level. e.g. Open in read only mode, acquire write lock when needed.
http://msdn.microsoft.com/en-us/magazine/cc163730.aspx


### Lockless updates

* Expose Redis non-locking commands e.g. http://redis.io/commands/INCR, http://redis.io/commands/append, http://redis.io/commands/rpush etc - to allow for explicitly lockless modifying of session data.

### Explicit updating

Allow user to discard update?

### Security

* Notify clients when about invalid session IDs attempting to access the system.


* Allow implementing strategies for re-generating session IDs e.g. rules based on user I.P. changing, locking session to specific user-agent. 

* Force cookie to be http only by default.


### Management


* User should spawn a regular task to cleanup old sessions, rather than have them garbage collected randomly via existing processes


* Allow sessionIDs that have recently been regenerated to new session IDs to continue to access the same data for a short time to allow session regeneration with simultaneous Ajax requests to not be borked e.g. https://github.com/EllisLab/CodeIgniter/pull/1900



## Misc ideas

A redis pub-sub system where your session was subscribed to a pub-sub feed for the life of the request. If any other concurrent request modified the session your copy of the session would receive the publish update.

session_discard - why would that be needed?






## Why?


//TODO - the whole way PHP has abstracted sessions with these functions just
        //sucks. You should be building up a complete response and then sending everything at once,
        //Not sending a header when this function is called.
        session_start();
        
        
        
// session_destroy is evil - the session variables can still be set through setSessionVariable and they
        // will work for the same page view. They dissapear on the next page view though.
        // Setting the $_SESSION variable to an empty array deletes all previous entries correctly.



//PHP automatically modified GET session behaviour - 
/* Check whether the current request was referred to by
	 * an external site which invalidates the previously found id. */


/* Finally check session id for dangarous characters
	 * Security note: session id may be embedded in HTML pages.*/


## Questions


### Should session re-naming be supported?

### PHP currently 'encrypts' the session data?
