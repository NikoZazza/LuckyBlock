# LuckyBlock
<b>LuckyBlock</b> is an plugin that allows you to create <b>LuckyBlock</b>!

Official website and for more information, go <a href="http://devs4pm.eu/forums/resources/luckyblock.35/"> here</a>

#How Does It Work
    When a player breaks a block of Sponge(ID: 19),<br> the plugin performs one of the following commands:
       - spawn a tree.
       - an explosion.
       - drop an item.
       - spawn a bedrock.
       - spawn a prison.
       - spawn a chest with items.
       - give money to a player.
       - teleport a player to a default spawn location.

<hr>
#Commands
    Go to this <a href="http://xionbig.netsons.org/plugins/LuckyBlock/commands.php">page</a>

<hr>
#How To Install
    1. Turn off the server with the command /stop. 
    2. Put the file LuckyBlock.phar in the folder /plugins. 
    3. Start your server.

<hr>

#How To Configure
config.yml

    --- 
    #sets the block to activate the plugin
    lucky_block: 19
    
    #sets the block to be used for prison
    prison_block: 49
    
    #sets the minimum of money to give to the players 
    #(If you do not have the plugin PocketMoney, €¢onom¥$ or MassiveEconomy, 
    #then the plugin will not give money to the players)
    money_min: 0
    #sets the maximum of money to give to the players 
    money_max: 1
    
    #write here the item list(max allowed: infinite)
    #example
    items_dropped: 
    - "tnt"
    - "259"
    - "17:1"
    items_chest:
    - "tnt"
    - "259"
    - "17:1"
    
    #the maximum of the items that the plugin will put inside the chest
    max_chest_item: 4
    
    #the minimum radius of the explosion
    explosion_min: 1
    #the maximum radius of the explosion
    explosion_max: 3
    
    #turn on/off the plugin
    status: "on"
    
    #write here the world name where you want LuckyBlock works!
    #set this field empty if you want that works on all worlds in the server
    #if you want to apply only on two or more worlds, enter the names of the worlds separated by a comma
    #example level: world, hungergames, lobby
    level: []
<hr>

message.yml
    Go to this <a href="http://xionbig.netsons.org/plugins/LuckyBlock/translate/">page</a>
    
#Licence

    LuckyBlock Copyright (C) 2015 xionbig
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

<a href="https://twitter.com/xionbig">@xionbig</a>
