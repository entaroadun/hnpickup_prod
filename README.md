### News Data Mining Web App ###

Source of the data: [Hacker News](https://news.ycombinator.com/)

Various Views of the data: [Hacker News Pickup Ratio](https://hnpickup.appspot.com)

### Project Motivation ###

Hacker News Pickup Ratio aims to understand web user behavior. It seems that Hacker News models some kind of closed system of people. There are three types of customers that come to Hacker News: <b>finders</b>, <b>adopters</b> and <b>viewers</b>. Finders search the Internet for interesting information to <i>post</i> it on Hacker News. They might write their own posts. Adopters look at the <i>newest</i> articles and up vote them if they judge them to be interesting. Viewers wait for finders and adopters to do the quality check for them. They do not want to be bothered by a random noise of the Internet. Viewers just wait for the articles at the front <i>news</i> page.

This creates an interesting dynamics that generalizes over variety of marketing problems in the worlds of commercial products. This is the main reason why we set up to study these cohorts of users. Every finder is like
a product owner that hopes for a wide adoption. This cannot be achieved without aiming at people that are the most passionate about some narrow domain of life. They start evangelizing so the rest can listen and the product becomes profitable.

Knowing when is a good time to post on Hacker News is almost like knowing how to market a product. Hacker News simplifies this situation. All we need to know is how many adopters are currently reading the newest posts.
But this statistics is hidden from us – like in the real world. We can only monitor the number of votes per newest post. There is also competition lurking. If too many people are posting then none of the posts will get a chance to be up voted by the adopters. But once a post gets to the front news page, then there is just smooth sailing ...

Please enjoy the variety of the Hacker News data. Maybe they will bring you comfort or illusion that you know more than you did before you came here. We are hoping to find something too.

### Previous Version ###

There was a slight success with [earlier version](https://github.com/entaroadun/hnpickup) so we decided to continue this journey.

### Technology Stack ###

Application is build on top of Google App Engine using PHP (twig, silex, php-gds), JS (jquery, morrisjs, raphael, datatable, bootstrap), HTML/CSS (bootstrap, sb-admin-2, metisMenu, font-awesome), and little bit of R. Data is stored using Google version of NoSQL called *datastore*. We use Google Analytics to track app usage. 

### Conceptual Design ###

Application has three main components:

1. Data collection (ETL)
2. Data mining (DM)
3. Reporting (Visualization)

If all three parts are in a perfect harmony then we should get those glorified *actionable insights* that everyone is talking about.

### Privacy Statement ###

This app collects personal data. But this can be scrambled by manipulating Session Unique Identifier (SUID).

### Screenshot ###

![App Screenshot](https://raw.githubusercontent.com/entaroadun/hnpickup_prod/screenshots/app_screenshot.png)

