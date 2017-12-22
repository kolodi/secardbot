# secardbot
Shadow Era card bot


Main entry point is _bot.php_, it is an endpoint registered with webhook. To register an endpoint on your server you can use _register.php_. You also need a TG bot token.
Here is an example of input JSON from telegram server
```javascript
{
  "update_id": 440148654,
  "inline_query": {
      "id": "817150061370106850",
      "from": {
          "id": 160297514,
          "is_bot": false,
          "first_name": "Kolodi",
          "username": "kolodi",
          "language_code": "en-US"
      },
      "query": "ally warrior foil",
      "offset": ""
  }
}
```
User and query is alwasy saved to the database for analitics.
A TG object is responsable for sending results back to the telegram, DB is a helper class for database communication. You should use a database called "se", tables dump can be found in folder _db_backup_. Also don't forget to create a custom db user (username: "se", password "se"). Database tables were populated semiautomatically using _secards.352m.json_ as input with helper scripts: _json_to_db.php_ and _db_cards_classes_link_generator.php_.

Query is processed in _process_query.php_, all the "magic" is there. It is responsable to evaluate an input string and create appropriate database query to search for 1 or more cards. At the end it returns an **InlineQueryAnswer** object with array of **PhotoResult**s.
Here is an example of output **inline query result** to send back to the telegram server:
```javascript
{
    "inline_query_id": "59e88b3a13294",
    "results": [{
        "type": "photo",
        "id": "59e88b3a13c95",
        "photo_url": "http:\/\/www.shadowera.com\/cards\/ex021.jpg",
        "thumb_url": null,
        "title": "Disciple of Aldmor",
        "caption": null,
        "photo_width": null,
        "photo_height": null
    }, {
        "type": "photo",
        "id": "59e88b3a13cab",
        "photo_url": "http:\/\/www.shadowera.com\/cards\/ll036.jpg",
        "thumb_url": null,
        "title": "Child of Aldmor",
        "caption": null,
        "photo_width": null,
        "photo_height": null
    }],
    "cache_time": 300
}
```

Notice cache time property. Telegram is caching results on its server, so if users type same query within the cache time no request will be forwarded to the _bot.php_ script, that's why cache time is set to 0 when query is about random card.

There are no card images on my server, all images are still referenced from shadowera.com server
