Description:
---------------
The Lime Survey API is a Drupal 8 module provides multiple blocks, For each block the data is retrieved by making calls to the LimeSurvey API.

In order to use this module, you will need to create a lime survey Instance where you enable the API .
The Details can be found here https://manual.limesurvey.org/RemoteControl_2_API#List_of_functions


Once you have obtained your API user, you can go to the LimeSurvey 
settings page and fill in the credentials.

How to Install:
---------------
Setup your separate LimeSurvey instance and create an API user at  https://manual.limesurvey.org/RemoteControl_2_API#List_of_functions
Enable this module
Go to admin/config/services/limesurvey/settings
Fill in your api user creds and the LimeSurvey url.
Once you hit submit, the block(s) will be created under admin/structure/block with the same name as that of the Survey 
Place your blocks on the appropriate page
