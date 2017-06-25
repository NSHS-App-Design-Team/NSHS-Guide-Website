# This repository exists for BACKUP ONLY #

* DO NOT edit this repository directly. Always edit the server files directly, then push to this repository to back up the files.
* Pull, then push to this repository whenever you're done editing the server files. COPY the server files over here every time; don't move the actual files (or else nothing will be on the server)

### How do I get set up? ###

1. Download newest trial version of [PhpStorm](https://www.jetbrains.com/phpstorm/download/). When asked to register, click "License Server" and provide this link **http://idea.qinxi1992.cn/**. This works for anything created by Jetbrains, although you may have to restart the computer if it doesn't work
2. In PhpStorm, select "Create New Project From Existing Files"
3. Select "Web server is on remote host, files are accessible via FTP/SFTP/FTPS"
4. Give project a name, set saved location in "local path" (in an empty folder), choose "Custom" in "Deployment Options
in Upload changed files automatically to the default server", choose "On explicit save action"
5. Select "Add new remote server"
6. Name = "NSHS Guide Website", Type = "SFTP", SFTP Host = "nshsguide.newton.k12.ma.us", Port = "22", User name = "nshsguideadmin", Auth type = "Key pair (OpenSSH or PuTTY)", Private key file = wherever you put "private_key.ppk" from the repository
7. Open the folders in this order: var -> www -> html
8. Click html so it's highlighted, Click "Project Root" (top left)
8. click Next, then Finish
9. Import settings (called either settings-windows.jar or settings-mac.jar)
10. That's it! (FOR PHPSTORM)

### How to get Polymer web components working? ###

1. Install [Node.js](http://nodejs.org/)
2. Download [Git](http://git-scm.com/); be sure to allow using Git from command line during the setup process
3. Install Bower with this line 
```
#!command line

npm install -g bower
```
4. Whenever you need to update Polymer libraries, "Shift + Right click" in the root directory of the project, open cmd prompt there (in the selection menu), and run
```
#!command line

bower update
```

### How to get PHPStorm Data Sources (MySQL) with autocomplete? ###

1. Open "Database" on top right corner under the search icon
2. Click the plus button, add MySQL data source
3. Fill out the info as follows:
Host: localhost, Database: nshsguid_data, User: root, Password: the one you know
4. Click the "SSH/SSL" tab
5. Fill out the info as follows:
Proxy host: nshsguide.newton.k12.ma.us, Port: 22, Proxy user: nshsguideadmin, Auth type: Key Pair (OpenSSH), Private key file: (wherever you put your key)
6. Click OK, and you're all good

### Every time when you edit... ###

* **ALWAYS** select NSHS Guide Project under "Project" (top left), then click Tools -> Deployment -> Download from NSHS Guide Website **FIRST** to sync
* Save to see edits occur on [website](http://nshsguide.com) (you may have to disable cache & javascript and reenable them to see edits; this can be found with F12, then F1 on Chrome for Windows)
* Commit to BitBucket when you're done editing, then push