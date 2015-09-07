# LuckyBlock
LuckyBlocks is an plugin that allows you to create LuckyBlocks!

#How Does It Work
    When a player breaks a block of Sponge(ID: 19), the plugin performs one of the following commands:
    - spawn a tree
    - an explosion
    - drop an item
    - spawn a bedrock
    - spawn a prison
<hr>
#Commands
    - /luckyblock on = enable the plugin
    - /luckyblock off = disable the plugin

<hr>
#How To Install
    1. Turn off the server with the command /stop. 
    2. Put the file LuckyBlock.phar in the folder /plugins. 
    3. Start your server.

<hr>

#How To Configure
config.yml

    #write here the item list(max allowed: infinite)
    #example
    item:
    - "46"
    - "259"
    - "17:1"
    
    #the radius of the explosion (min. 1, max 30)
    explosion: 3
    
    #turn on/off the plugin
    status: "on"
    
    #write here the world name where you want LuckyBlock works!
    #set this field empty if you want that works on all worlds in the server
    #if you want to apply only on two or more worlds, enter the names of the worlds separated by a comma
    #example level: world, hungergames, lobby
    level: []
<hr>

message.yml

    ---
    tree: Tree spammed
    explosion: BOOOM!!!
    drop: Lucky
    sign: It's your problem!
    signText: It's your problem!
    prison: OPS...
    unlucky: Try again maybe you will be more lucky
    ...

#Features
-Chest
-Money
