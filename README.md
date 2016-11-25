Piksel php SDK
==============

A php development toolkit for [Piksel API v5](https://ovp.piksel.com/services/docs/functions_overview.php?page=rest&apiv=5.0)

Installation
---------

Install dev dependencies within this directory :

    $ cd path/to/piksel-php-sdk/
    $ composer install


Documentation
---------

The documentation can be found in the ./docs/api directory.
Generate latest version with :

    $ composer exec phpdoc project:run -d ./src -t ./docs/api --title=Piksel\ PHP\ SDK

Unit tests
---------

If you want to run the unit tests, in the Piksel/Tests/bootstrap.php 
file you must define a $app config array as following :

     $config = array(
         'baseURL' => 'https://api-ovp.piksel.com', // The Piksel API base url
         'token' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // A Piksel API token
         'refIDPrefix' => '', // A prefix for handling same category names between two sub accounts
         'searchUUID' => 'xxxxxxxx', // A default project UUID to pickup videos
         'api' => array(
             'username' => 'xxxxxx', // Piksel API user name
             'password' => '******', // Piksel API user password
             'folderID' => '00000'   // Default media library folder ID
         ),
         'debug' => true // Optional, false by default
     );

Then run PHPUnit :

    $ composer exec phpunit

Authors
-------

- [Alex Druhet](http://listo.studio)

Contributors
-------

- [Fabrice Crapoulet](http://piksel.com)
- [Christophe Neria](http://kwaweb.com)

License
-------

The MIT License (MIT)

Copyright (c) 2015 Alex Druhet

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
