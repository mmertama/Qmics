Qmics comic book reader
    Copyright 2014 Markus Mertama markus.mertama @ gmail.com 

I needed a Comic book reader for a Windows Phone. I wrote already one for Symbian and Meego using Qt, but this time I needed more generic. I also wanted same application run on my iPad and Android TV - and keep library and current comic in sync. Therefore I rejected all C# etc. propiertary solutions and went to web application. 


This Qmics book reader let read comics with any device that runs HTML5 - but it needs a server. The server has to have pretty recent version of PHP and MariaDB / MySql and having UnRar and Unzip installed.

Usage: 
It is important to have a proper folder structure: If the library folder is myprivate_library, the first folder is "publisher" and the next ones makes title. e.g.
    
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_1.cbr. That generates library item "Narvel" "Spederman Hidden Wars" and all comic folders under that can be found under that title.
        
When logging first time as an "admin" the given password is set as a admin password, that, as any password can be changed from the settings page.        

Install:
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
        -stops processing to first warning
        

The server is so far tested only with Synology Diskstation - but is smoke tested e.g. with Windows Phone 8, iPhone/iPad iOS7, Android 4.x WebTV, pad and Samsung Galaxy S3, IE9 and Chrorome on Windows 7 and Windows 8, Safari, Chrome, Firefox on OSX, Chrome, Firefox on Linux Mint.



        


