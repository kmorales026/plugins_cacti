/*******************************************************************************

    Author ......... Petr Macek
    Contact ........ petr.macek@kostax.cz
    Home Site ...... http://www.kostax.cz/cacti
    Program ........ Intro/console screen with more information

*******************************************************************************/


----[ Purpose

	This plugin add information and small preview of hosts, tholds and logs statitics, ...

----[ Installation

	Copy directory intropage to plugins directory
	Check file permission (Linux/unix - readable for www server)
	Enable plugin (Console -> Plugin management)
	Configure plugin (Console -> Settings -> Intropage tab)
	You can set Intropage as first page (Console -> User managemnt -> user -> Login Options) 
    
----[ Upgrade    

	Copy and rewrite files
	Check file permission (Linux/unix - readable for www server)
	Disable and deinstall old version (Console -> Plugin management) 
	Install and enable new version (Console -> Plugin management) 
	Configure plugin (Console -> Settings -> Intropage tab)
    
----[ Possible Bugs?
   
	If you find a problem, let me know!

----[ Future Changes

	Got any ideas or complaints, please e-mail me!

----[ Thanks

	Earendilfr, Tomas Macek, Peter Michael Calum, Trevor Leadley

----[ Changelog
      0.9 ---
    Cacti 1.0 compatibility - thank you, Earendilfr!
    Graphs in chart.js - thank you, Earendilfr!
	    0.8 ---
		Add set default setting after plugin install
		Add few settings (host without graph, host without tree, ...)
		Add Subtree  name in "Devices in more then one tree" (thank you, Hipska)
		Add debug option
		Add db check level
		Fix warning and notices (thank you, Ugo Guerra)
    		Fix thold graph - triggered, breached (Thank you, Hipska)
    		Fix last x lines log lines (thank you, Ugo Guerra)
	    0.7 ---
		Add number rounding
		Add switch for default page setting for users without console access
		Add layout Best fit
		Fix layout 
		Fix database check - memory tables cause false warnings
		Fix redirect function
		Fix return default page after unistall plugin
		Fix displaying links if user hasn't right
		Redesign Pie graphs (author: Trevor Leadley)
	    0.6 ---
		Add separated Tab for plugin
		Add Cacti user rights (limited user cannot see all statistics but statistics of authorized equipment)
		Add cacti database check
		Add more information in logs
		Add Search FATAL errors in log  
		Fix NTP function (infinite loops if wrong ntp server is used)
		Redesign Pie graphs (author: Trevor Leadley)
		Redesign (author: Tomas Macek)
	    0.5 ---
		Change time check (now via NTP)
		Add settings to Console -> Settings -> Intropage
		Add Mactrack 
		Add more pie graphs
		Redesign
	    0.4 ---
		Add pie graphs (need PHP GD library)
		Add checks for:
		- device with the same name
		- device without tree
		- device more times in tree
		- device without graphs
		Add Top 5:
		- the worst ping response
		- the lowest availability
		Fix php notices and warnings
		Redesign
	    0.3 ---
		Add OS and poller information
		Add time check
		Add control poller duration
		Add icons
	--- 0.2 ---
		Fix error - number of all tholds
	--- 0.1 ---
		Beginning
