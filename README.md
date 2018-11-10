# Jay's Aliexpress WooCommerce Importer

> This code is in **alpha** and should not be considered stable. This was designed as a proof of concept. I may at some
point continue to develop on this code, but for now, it will stay in alpha. Feel free to fork it and do as you please.

## Description

This plugin allows you, the user, to import an Aliexpress product directly from their website. To do this, it creates an 
**API** where one doesn't exist by utilizing website scraping and transformations.

[_( See the full JSON output - formatted of course )_](https://gist.github.com/JayWood/9e9d3e1731cee5ff9e92d539492d9853)
![](https://i.gyazo.com/5a0d5042bfccd324ba52011fa4cc4853.png)

To be clear, this is **not** created **with** an API - I created the API with the HTML content from the page, as well
as a few inline JS requests that were observed during the page loading transactions. _( Network inspector is your friend. )_

## WooCommerce Import Functionality
The idea behind creating a sort of API interface to read from was for ease of use to import content to just about any
platform be it WooCommerce, Shopify, etc... Essentially the API aspect could be easily decoupled into it's own library
whereas it could then be used for just about anything. It's a scraper after all!

This plugin leverages the WooCommerce export form with a bit of added javascript to keep a consistent WooCommerce
experience.

![](https://i.gyazo.com/b73b2f87c8395166ceb9677f1d6597af.gif)