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

This plugin leverages the WooCommerce export form with a bit of added javascript and removed text to keep a consistent
WooCommerce experience. I admit, I copied the HTML :smile:

![](https://i.gyazo.com/b73b2f87c8395166ceb9677f1d6597af.gif)

## What this plugin does NOT do
There are many TODO's here, to be fair, I just wanted to see how difficult it would be to create an API from a scraped
HTML page. But enough of that, here's what the intent was, but I never got to it!
* Import product updates on a regular basis
* Import Images ( data is saved to product meta with the intent to process on save )
* Import Variations ( data is saved to product meta with the intent to process on save )
* Import Categories ( Aliexpress categories may differ from your WordPress store )

### A couple gotcha's when I was working on this
1. Aliexpress does NOT like the default WordPress header given from `wp_remote_get()` - Faking the headers allows a request to happen normally.
1. Shipping information is loaded asynchronously from an external API - to which you have to provide request parameters to get the data for the specific item.
1. Description is loaded asynchronously as well from an external API, it's not readily available.
1. Product variations had to be scraped from a JavaScript variable, learning to read that using DomDocument ( DiDom library in this case ) was interesting.

On the plus side, I learned a lot about creating an API object from an HTML source. Hope you will to.
If you use this, please ping me on twitter [@plugish](https://twitter.com/plugish) - would love to see what you create!

If you'd like to [buy me a beer](https://paypal.me/jaywood), I won't turn you down!

( A WordPress plugin by [Jay Wood](https://www.plugish.com) )
