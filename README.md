![STAG: A Statamic CLI](http://assets.thisbythem.com.s3.amazonaws.com/blog/jw-stag.jpg "STAG: A Statamic CLI")

## What is it?
It's the command line interface for Statamic. It can do all sorts of
wonderful things for you like:

- Deploy your code to a server (via git, rsync or ftp)
- Pull your content down from a server (via git, rsync or ftp)
- Clear your site cache -- locally or remotely
- Set file permissions -- locally or remotely

## Who is it for?
You're a good looking Statamic developer who loves the command line. You
believe that `stag clear_cache` is much better than using Finder to
clear those directories manually.

## DISCLAIMER
Stag is in beta. It has been used on OSX to talk to an Ubuntu server.

It will be manipulating files and setting permissions on things, so if
you aren't comfortable with the command line, or your site isn't under
some sort of version control, then stag might not be for you... yet. Testers welcome!

## Installing
Download the repo, unzip and drop the stag directory into your
\_add-ons. _(You'll need to rename stag-master to stag)_

### Using stag
You need to add the stag bin directory to your $PATH. In your
.bash_profile (or your prefrerred shell's config) drop in this line:

```
export PATH=$PATH:_add-ons/stag/bin
```

Reload your config file:

```
source ~/.bash_profile
```

Type `stag` in your Statamic root directory and if you are greeted by a
couple of bucks, Congratulations! Stag is installed!

### Getting Started
To list all the commands, type: `stag help`

For any command that will perform tasks on your server, you'll need to
have setup [passwordless ssh
access](http://www.thegeekstuff.com/2008/11/3-steps-to-perform-ssh-login-without-password-using-ssh-keygen-ssh-copy-id/).
It's relatively easy-to-do and makes things much more secure. If that's
ready to go, you'll need to configure stag to talk to your server. Copy
the default config to `_config/add-ons/stag.yaml`

[Server Configuration](https://github.com/thisbythem/stag/wiki/Server-Configuration)

You can read more in-depth about each command here:
[Wiki](https://github.com/thisbythem/stag/wiki)

### Support
Again, stag is in beta, so if you encounter something not working
properly, please [create an
issue](https://github.com/thisbythem/stag/issues/new) and we'll to take
a look.

### Contribute
If you have an idea of something you'd like stag to do, suggest it here:
[support@thisbythem.com](mailto:support@thisbythem.com?Subject=Stag Ideas)
or feel free to fork, hack and pull request. Happy Hacking!
