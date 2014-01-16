#Kohana ACL

ACL module for Kohana >= 3.3 based on [Wouterrr/ACL](https://github.com/Wouterrr/ACL) + [Wouterrr/A2](https://github.com/Wouterrr/A2)

##Dependencies

[kohana-pack/timestamped-migrations](https://github.com/kohana-pack/timestamped-migrations)
[kohana/database](https://github.com/kohana/database)
[kohana/auth](https://github.com/kohana/auth)

##Installation

First off, download and enable the module in your bootstrap.

Add the necessary tables and fields in the database using migrations:
<pre>
$ ./minion db:migrate --module=kohana-acl
</pre>


###Settings

..........


###Usage

####Simple usage
<pre>
// Add resource
ACL::instance()->add_resource('news');
// Add role
ACL::instance()->add_role('guest');
ACL::instance()->add_role('member');
ACL::instance()->add_role('admin');
// Allow "guest" to "view" the news
ACL::instance()->allow('guest', 'news', 'view');
// Allow "member" to "comment" on "news"
ACL::instance()->allow('member', 'news', 'comment');
// Allow "editor" to do anything, except "delete" news
ACL::instance()->allow('editor', 'news');
ACL::instance()->deny('editor', 'news', 'delete');
// Allow "admin" to do anything
ACL::instance()->allow('admin');
</pre>

Check permissions for current user
<pre>
ACL::check('news', 'edit'); // return boolean value
</pre>

Check permissions for any user
<pre>
ACL::instance()->is_allowed('guest', 'news', 'comment');
ACL::instance()->is_allowed('editor', 'news', 'add');
ACL::instance()->is_allowed('admin', 'news', 'delete');
</pre>
