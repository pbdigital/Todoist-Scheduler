# Todoist-Scheduler

## Disclaimer

Let me start with a disclaimer :-)

I made this script as a little side projects over the xmas holidays and have been using it with my own Todoist setup to automate what I was doing manually. Feel free to use, modify, etc... what I have but chances are that you'll need to modify the code to get it to work with you're setup, google accounts, etc...

Hope it helps though

## How It Works

I've been using Todoist Shortcuts (https://github.com/mgsloan/todoist-shortcuts) the past few months and simply added a new keybiding and function into todoist-shortcuts.js which I've included in this repo.

That JS funciton parses the tasks I've selected to extract the Task ID, task name and duration or each task

**Duration**

For the tasks I schedule, they will have one of the following labels which is used to determine duration.

15_min
30_min
45_min
60_min
75_min
90_min
105_min
120_min

A JSON array is sent over to the PHP file on my server which does the following:

1. Pulls events from my Google Calendars 
2. Attempts to schedule each task
3. When it finds a suitable time to schedule the task, it'll update Todoist.

I'm also using Tascaly, so when the date and time is updated in Todoist, It'll then add that event to my Google Calendar. I could add directly to my Google Calendar but that was quicker and easier to Implement as I was already using Tascaly and have an annual subscription with 11 months left.
