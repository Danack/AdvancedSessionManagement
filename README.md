sessio
======

Advanced session data


Ideas
=====

A redis pub-sub system where your session was subscribed to a pub-sub feed for the life of the request. If any other concurrent request modified the session your copy of the session would receive the publish update.


Be explicit and expose locking similar to how databases expose different levels of locking, and allow applications select the appropriate level. e.g. Read only locks
http://msdn.microsoft.com/en-us/magazine/cc163730.aspx




Expose Redis non-locking commands e.g. http://redis.io/commands/INCR, http://redis.io/commands/append, http://redis.io/commands/rpush etc - to allow for explicitly lockless modifying of session data.


Support multiple sessions per user, to allow session regeneration with simultaneous Ajax requests to not be borked e.g. https://github.com/EllisLab/CodeIgniter/pull/1900

Allow strategies for I.P. changing, regenerating session IDs, logging invalid SessionID attempts.
Store useragent as part of session info, 


Force http only obv.
