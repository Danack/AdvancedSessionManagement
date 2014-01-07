# Advanced session management




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
