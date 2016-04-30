# EnhancedStudentNews
A web interface for reading Calvin College's daily announcements emails. Each news item title listed at the beginning of an issue is a link to a particular news item.

Note: Enhanced Student News is not affiliated with or endorsed by Calvin College.

## Copyright and License
Copyright (C) 2016 Daniel Harold.

Daniel Harold hereby makes this software available under the GNU Lesser General Public License version 2 or, at your option, any later version. Contact Daniel if you are interested in other licensing options.

## Requirements
You will need to have PHP with the `curl` module installed to run Enhanced Student News.

## Configuration
To configure Enhanced Student News, copy `config.sample.php` to `config.php` and at least change the `BASE_SOURCE_URL` setting to a valid news source. Then you should be able to open `src/view.php` in a web browser and read the most recent news issue from the source you configured.

## Warning
If you host this application on the Internet, please set up a `robots.txt` file to prevent the news content from being indexed by search engines. Please be aware that this software does not seek to honor the `robots.txt` directives from the news source.

## History
The first version of Enhanced Student News was written on October 12, 2013, by Calvin College student Daniel Harold. The bulk of the code was in a single script, a single procedure with no object-oriented code.

Then, in December 2013 and January 2014, Daniel rewrote Enhanced Student News, this time with an object-oriented approach. The code from this rewrite, sometimes called the 2014 version, is the base for this repository.

As of April 2016, the older version is the one used for automatic emails, as the HTML and CSS used in the newer version do not render well in Microsoft Outlook.
