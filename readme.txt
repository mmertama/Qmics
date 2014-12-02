Qmics - comic book reader and server

Copyright 2014 Markus Mertama markus.mertama @ gmail.com 
https://github.com/mmertama/Qmics
    
Generic comic book reader for all platforms that have a HTML5 capable web browser; PCs, Smart TVs, Android, iOS, Windowses etc. 

I needed a Comic book reader for a Windows Phone. Long ago I implemented one for Symbian and Meego using Qt (may still be available at Ovi/Opera Store), but this time I wished a more generic solution. I also wanted the same application run on my iPad and Android TV, and keep library and the currently read comic in sync. Therefore I abandoned propiertary solutions and chose to do a web application. 

The Qmics book reader let user to read comics with any device that runs HTML5, it consists of client side Javascript and server side PHP parts. It has been developed on Apache. The server has to have pretty recent version of PHP and MariaDB / MySql and having UnRar and Unzip installed. The Javascript I quite compliant with all major browsers, but maybe now I  would consider jQuery. 

Install:

Nothing has to be installed in client (the reading device) - just open a browser. The server side installation needs scripts and library files in the correct locations.

Qmics has two folders: Qmics application folder and a library folder. The application folder is created when Qmics content is copied into web server and that defines URL of of the library, e.g. if Qmics is copied in the server www.foo.com and its web folder Qmics, the login page URL is http://www.foo.com/Qmics/qmics.html. Web server has to have a read access to all files in the folder. 
Library is the folder from where data is read. If caches are in the same folder also web server write access is needed. It may be feasible to prevent HTTP access to that folder (e.g. using .htaccess file). 

Copy all comics archive files in that folder:

It is important to have a proper folder structure. It is assumed that each of the archive is stored under certain folder hierarchy: The publisher / title. e.g.
    
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_1.cbr
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_2.cbr
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_3.cbr
        
That generates library item where publisher is "Narvel" and title "Spederman Hidden Wars" and each archive can be found under that.
       
When logging first time as an "admin" the given password is set as a admin password, that, as any password can be changed from the settings page.        


Configuration:

After copied all files from repository to web server - open script/configure.php 

    * define('LIBRARY', "../../myprivate_library");
        -Set this value to folder where cbr, rar, cbz and zip files are copied.
    
    * define('COVER_CACHE',"../../myprivate_library/cache/covers");
        -Place where covers pages are stored.
    
    * define('CURRENT_CACHE',"../../myprivate_library/cache/current");
        -Place where currently open comics are deflated.
    
    * define('ERROR_LOG',"../../myprivate_library/errorlog.txt");
        -Place where problems are logged (if any)
    
    * define('COVER_WIDTH', 200);    
    * define('COVER_HEIGHT', 200);
        -Maximum width and height  of the cover image - you should be able to modify Qmics very freely using CSS. 
    
    * define('LOGOUT_PAGE',"../qmics.html");
        -Define where to go after logout.
    
    * define('MIN_PASSWORDLEN', 5);
        -Minimum password length.
    
    * define('ZIPCMD', "/usr/syno/bin/zip");
        -system path to zip command
    
    * define('UNZIPCMD', "/usr/syno/bin/unzip");
        -system path to unzip command
        
    * define('UNRARCMD', "/usr/syno/bin/unrar");
        -system path to unrar command
    
    * define('DB_ADDRESS', 'localhost');
        -address of database
        
    * define('DB_USER', 'qmics');
        -db username
        
    * define('DB_PASSWD', 'qmicspasswd');
        -db password
        
    * define('WARNINGS_FATAL', false);
        -stops processing at the first warning
        
    * define('DEBUG_LOG', false);
        -defines folder to write debug log (developer can add debug_log(string) functions)


Settings:

When logged as an "admin" the Settings (login -> Library -> Settings) 
* "User properties"
    Filters helps adjust individual settings. 
    Each comic read and download access can be set invidually by ticking boxes and submitting.

* User management: "Add User", "Change password" and "Delete user".

* "Generate Comics DB" - when ever archive files are changed (add / deleted / moved) the DB must be regenerated. The user access values shall be preserved.

* "Clear Caches" - After cover size all cover pages has to be regenerated and caches emptied.

The server is so far tested only with Synology Diskstation - but is smoke tested e.g. with Windows Phone 8, iPhone/iPad iOS7, Android 4.x WebTV, pad and Samsung Galaxy S3, IE9 and Chrorome on Windows 7 and Windows 8, Safari, Chrome, Firefox on OSX, Chrome, Firefox on Linux Mint.



        


