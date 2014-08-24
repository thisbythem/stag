# STAG

## What is it?
It's the command line interface for Statamic. It can do all sorts of
wonderful things for you like:

- Deploy your code to a server (via git, rsync or ftp)
- Pull your content down from a server (via git, rsync or ftp)
- Clear your site cache; locally or remotely
- Set file permissions; locally or remotely

## Who is it for?
You're a good looking Statamic developer who loves the command line. You
believe that `stag clear_cache` is much better than using Finder to
clear those directories manually.

## DISCLAIMER
Stag is in a **very** beta version. It has only been tested on OSX. And has
only talked to an Ubuntu server. It will be manipulating files and
setting permissions on things, so if you aren't comfortable with the
command line, or your site isn't under some sort of version control,
then stag might not be for you... yet.

## Installing
Download the repo, unzip and drop the stag directory into your
\_add-ons. _(You'll probably need to rename stag-master to stag)_

### Using stag
You need to add the stag bin directory to your $PATH. In your
.bash_profile (or your prefrerred shell's config) drop in this line:

```
export PATH=$PATH:/_add-ons/stag/bin
```

Reload your config file:

```
source ~/.bash_profile
```

And type `stag` in your Statamic root directory. If you see:

```
Stag lets you Statamic-it-up on the command line.
Usage: stag <command> <options>
Try stag help to list all commands.
```
Congratulations! Stag is installed!

### Getting Started
To list all the commands, type: `stag help`

For any command that will perform tasks on your server, you'll need to
configure stag to talk to your server. Copy the default config from the
add on directory and copy it to `_config/add-ons/stag.yaml`

You can read how to configure it in here: [Server
Configuration](https://github.com/thisbythem/stag/wiki/Server-Configuration)

You can read how to configure each command here:
[Wiki](https://github.com/thisbythem/stag/wiki)
