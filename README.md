#Kohana ACL

ACL module for Kohana >= 3.3 based on [Wouterrr/ACL](https://github.com/Wouterrr/ACL) + [Wouterrr/A2](https://github.com/Wouterrr/A2)

###Dependencies

- [Kohana-pack/Timestamped-Migrations](https://github.com/kohana-pack/timestamped-migrations)
- [Kohana/Database](https://github.com/kohana/database)
- [Kohana/Auth](https://github.com/kohana/auth)

###Installation

First off, download and enable the module in your bootstrap.

Add the necessary tables and fields in the database using migrations:
<pre>
$ ./minion db:migrate --module=kohana-acl
</pre>


###Settings

..........


###Usage

####Simple usage without file config or database
Add resource
<pre>
ACL::instance()->add_resource('news');
</pre>
Add roles
<pre>
ACL::instance()->add_role('guest');
ACL::instance()->add_role('member');
ACL::instance()->add_role('editor');
ACL::instance()->add_role('admin');
</pre>
Allow "guest" to "view" the news
<pre>
ACL::instance()->allow('guest', 'news', 'view');
</pre>
Allow "member" to "comment" on "news"
<pre>
ACL::instance()->allow('member', 'news', 'comment');
</pre>
Allow "editor" to do anything, except "delete" news
<pre>
ACL::instance()->allow('editor', 'news');
ACL::instance()->deny('editor', 'news', 'delete');
</pre>
Allow "admin" to do anything
<pre>
ACL::instance()->allow('admin');
</pre>

Check permissions for current user
<pre>
ACL::check('news', 'edit'); // return boolean value
</pre>

Check permissions for any role
<pre>
ACL::instance()->is_allowed('guest', 'news', 'comment');
ACL::instance()->is_allowed('editor', 'news', 'add');
ACL::instance()->is_allowed('admin', 'news', 'delete');
</pre>


###Drivers

####Use Auth as driver
See [demo application](https://github.com/dimkof/kohana-acl-demo-with-auth)
or use default driver which is wrapper for Auth.

####Creating new driver
Class must implement interface ACL_Auth_Interface.
For example see [default driver](https://github.com/dimkof/kohana-acl/blob/3.3/master/classes/Kohana/ACL/Auth.php)
