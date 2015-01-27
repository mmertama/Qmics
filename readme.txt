Qmics - comic book reader and server

Copyright 2014 Markus Mertama markus.mertama @ gmail.com 
https://github.com/mmertama/Qmics

Version 2
    
Generic comic book reader for all platforms that have a HTML5 capable web browser; PCs, Smart TVs, Android, iOS, Windowses etc. 

I needed a Comic book reader for a Windows Phone. Long ago I implemented one for Symbian and Meego using Qt (may still be available at Ovi/Opera Store), but this time I wished a more generic solution. I also wanted the same application run on my iPad and Android TV, and keep library and the currently read comic in sync. Therefore I abandoned proprietary solutions and chose to do a web application. 

The Qmics book reader let user to read comics with any device that runs HTML5, it consists of client side Javascript and server side PHP parts. It has been developed on Apache. The server has to have pretty recent version of PHP and MariaDB / MySql and having UnRar and Unzip installed. The Javascript I quite compliant with all major browsers, but maybe now I  would consider jQuery. It's shall be easy to get running on your PC (assuming you have installed a web server) or web hosting service. I have developed on Synology Diskstation.  

Install:

Nothing has to be installed in client (the reading device) - just open a browser. The server side installation needs scripts and library files in the correct locations.

Qmics has two folders: Qmics application folder and a library folder. The application is simply the folder Qmics files are copied (copy, git clone or unzip) at web server.  That folder is the URL of of the service, e.g. if Qmics is copied in the server www.foo.com and its web folder Qmics, the login page URL is http://www.foo.com/Qmics/qmics.html. Web server has to have a read access to all files in the folder (write access if debug logging is used).
 
Library is the folder from where data is read. If caches are in the same folder also web server write access is needed. It may be feasible to prevent HTTP access to that folder (e.g. using .htaccess file) to avoid unsecure access to the library content. 

Copy all comics archive files into library folder - It is important to have a proper folder structure. It is assumed that each of the archive is stored with spesific folder hierarchy: Library / publisher / title / archives. 

	e.g. :
    
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_1.cbr
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_2.cbr
        myprivate_library/Narvel/Spederman/Hidden Wars/hidden_wars_3.cbr
        
That generates library item where publisher is "Narvel" and title "Spederman Hidden Wars" and each archive can be found under that.    

Configure:

1) When logging first time (set user nameto "admin" and some password) a configuration tool is opened.
2) There you have to set at least library and cache paths and username and password of the database.
3) After configuring login again using "admin" and provide the admin password, that, as any password, it can be changed from the settings page.     


Configuration settings:

    * LIBRARY - Set this value to folder where cbr, rar, cbz and zip files are copied.
    
    * COVER_CACHE - Place where covers pages are stored.
    
    * CURRENT_CACHE - Place where currently open comics are deflated.
    
    * ERROR_LOG -Place where problems are logged (if any)
    
    * COVER_WIDTH, COVER_HEIGHT - Maximum width and height of the cover image in the library view - you should be able to modify Qmics very freely using CSS. 
    
    * ZIPCMD - system path to zip command.
    
    * UNZIPCMD - system path to unzip command.
        
    * UNRARCMD - system path to unrar command.
    
    * DB_ADDRESS -address of database.
        
    * DB_USER - db username.
        
    * DB_PASSWD -db password.
        

Settings:

When logged as an "admin" the Settings (login -> Library -> Settings) 

* "Configure"
	You can reconfigure installation.

* "Set Max Size"
	(also for users) - set images to be rendered smaller size to speed up downloading. 	
	
* "User properties"
    Filters helps adjust individual settings. 
    Each comic read and download access can be set invidually by ticking boxes and submitting.

* User management: "Add User", "Change password" and "Delete user".

* "Generate Comics DB" - when ever archive files are changed (add / deleted / moved) the DB must be regenerated. The user access values shall be preserved.

* "Clear Caches" - After cover size all cover pages has to be regenerated and caches emptied.

* "Upload" - Some servers has limits of HTTP upload, please use SMB or such to copy comics archives. I maybe someday implement a workaround to support "limitless" upload, but so far SMB has been superior and this was only for testing.

The server is so far tested only with Synology Diskstation - but client has been tested e.g. with Windows Phone 8, iPhone/iPad iOS7, Android 4.x WebTV, pad and Samsung Galaxy S3, IE9 and Chrorome on Windows 7 and Windows 8, Safari, Chrome, Firefox on OSX, Chrome, Firefox on Linux Mint.



        


