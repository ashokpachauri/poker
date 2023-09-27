<?php
$addons->get_hooks(
    array(),
    array(
        'page'     => 'includes/language.php',
        'location'  => 'definitions'
    )
);

// More
ops_define( 'STATS_MSWG_FILE_SIZE', __( 'File size is too big', 'core' ) );


// All Page Headings
// -----------------------------------------------------------

ops_define( 'HOME', __( 'Welcome To', 'core' ) . ' ' . TITLE ); // TITLE should be ops_defined as a constant
ops_define( 'LOGIN', __( 'Login', 'core' ) );
ops_define( 'CREATE', __( 'Create Player', 'core' ) );
ops_define( 'LOBBY', __( 'Game Lobby', 'core' ) );
ops_define( 'RANKINGS', __( 'Player Rankings', 'core' ) );
ops_define( 'MY_PLAYER', __( 'My Player', 'core' ) );
ops_define( 'RULES', __( 'Poker Rules', 'core' ) );
ops_define( 'FAQ', __( 'Frequently Asked Questions', 'core' ) );
ops_define( 'ADMIN_LABEL', __( 'Admin', 'core' ) );
ops_define( 'LOGOUT', __( 'Log Out', 'core' ) );
ops_define( 'SITOUT', __( 'Sit Out Page', 'core' ) );

// Main Menu
// -----------------------------------------------------------

ops_define( 'MENU_HOME', __( 'Home', 'core' ) );
ops_define( 'MENU_LOGIN', __( 'Login', 'core' ) );
ops_define( 'MENU_CREATE', __( 'Register', 'core' ) );
ops_define( 'MENU_LOBBY', __( 'Lobby', 'core' ) );
ops_define( 'MENU_RANKINGS', __( 'Rankings', 'core' ) );
ops_define( 'MENU_MYPLAYER', __( 'Profile', 'core' ) );
ops_define( 'MENU_RULES', __( 'Rules', 'core' ) );
ops_define( 'MENU_FAQ', __( 'FAQ', 'core' ) );
ops_define( 'MENU_ADMIN', __( 'Admin', 'core' ) );
ops_define( 'MENU_LOGOUT', __( 'Log Out', 'core' ) );

// Table Types & Poker Page
// -----------------------------------------------------------

ops_define( 'CASHGAMES', __( 'Cash Games', 'core' ) );
ops_define( 'SITNGO', __( 'Sit \'n Go', 'core' ) );
ops_define( 'TOURNAMENT', __( 'Tournament', 'core' ) );
ops_define( 'DEALER_INFO', __( 'DEALER INFORMATION', 'core' ) );
ops_define( 'TABLEPOT', __( 'TABLEPOT:', 'core' ) );
ops_define( 'BUTTON_LEAVE', __( 'LEAVE TABLE', 'core' ) );
ops_define( 'BUTTON_SEND', __( 'Send', 'core' ) );

// Top 6 players mod
// -----------------------------------------------------------

ops_define( 'PLACE_POSI_1', __( 'st', 'core' ) );
ops_define( 'PLACE_POSI_2', __( 'nd', 'core' ) );
ops_define( 'PLACE_POSI_3', __( 'rd', 'core' ) );
ops_define( 'PLACE_POSI', __( 'th', 'core' ) );
ops_define( 'PLACE', __( 'Place', 'core' ) );

// Login Page
// -----------------------------------------------------------

ops_define( 'BOX_LOGIN', __( 'Player Login', 'core' ) );
ops_define( 'LOGIN_USER', __( 'Player Name:', 'core' ) );
ops_define( 'LOGIN_PWD', __( 'Password:', 'core' ) );
ops_define( 'BUTTON_LOGIN', __( 'Login', 'core' ) );
ops_define( 'LOGIN_NEW_PLAYER', __( 'Register', 'core' ) );
ops_define( 'LOGIN_MSG_APPROVAL', __( 'Account pending approval', 'core' ) );
ops_define( 'LOGIN_MSG_BANNED', __( 'This account has been banned!', 'core' ) );
ops_define( 'LOGIN_MSG_INVALID', __( 'Invalid Login!', 'core' ) );

// Sitout Page
// -----------------------------------------------------------

ops_define( 'SITOUT_TIMER', __( 'Sit Out Timer', 'core' ) );

// Create Player Page
// -----------------------------------------------------------

ops_define( 'BOX_CREATE_NEW_PLAYER', __( 'Create Your Player', 'core' ) );
ops_define( 'BOX_CREATE_NEW_AVATAR', __( 'Choose Your Avatar', 'core' ) );
ops_define( 'CREATE_PLAYER_NAME', __( 'Player Name:', 'core' ) );
ops_define( 'CREATE_PLAYER_PWD', __( 'Password:', 'core' ) );
ops_define( 'CREATE_PLAYER_CONFIRM', __( 'Confirm:', 'core' ) );
ops_define( 'CREATE_PLAYER_EMAIL', __( 'Email Address:', 'core' ) );
ops_define( 'CREATE_PLAYER_CHAR_LIMIT', __( '[ 5-10 chars ]', 'core' ) );
ops_define( 'CREATE_MSG_IP_BANNED', __( 'Your IP Address is banned!', 'core' ) );
ops_define( 'CREATE_MSG_MISSING_DATA', __( 'Missing fields, please try again.', 'core' ) );
ops_define( 'CREATE_MSG_AUTHENTICATION_ERROR', __( 'Authentication Error!', 'core' ) );
ops_define( 'CREATE_MSG_ALREADY_CREATED', __( 'You have already created a player!', 'core' ) );
ops_define( 'CREATE_MSG_INVALID_EMAIL', __( 'This email address is not valid.', 'core' ) );
ops_define( 'CREATE_MSG_USERNAME_TAKEN', __( 'Username already taken. Please try again.', 'core' ) );
ops_define( 'CREATE_MSG_USERNAME_MWCHECK', __( 'Username has too many m\'s or w\'s in it!', 'core' ) );
ops_define( 'CREATE_MSG_USERNAME_CHARS', __( 'Usernames can contain letters, numbers and underscores.', 'core' ) );
ops_define( 'CREATE_MSG_USERNAME_LENGTH', __( 'Your username must be 5-10 characters long.', 'core' ) );
ops_define( 'CREATE_MSG_PASSWORD_CHARS', __( 'Passwords can contain letters, numbers and underscores.', 'core' ) );
ops_define( 'CREATE_MSG_PASSWORD_LENGTH', __( 'Your password must be at least 4 characters long.', 'core' ) );
ops_define( 'CREATE_MSG_PASSWORD_CHECK', __( 'Your password and confirmation must match!', 'core' ) );
ops_define( 'CREATE_MSG_CHOOSE_AVATAR', __( 'Please select an avatar', 'core' ) );
ops_define( 'CREATE_APPROVAL_EMAIL_CONTENT', __( 'Thank you for applying to join our poker game. Please click the link to activate your player:', 'core' ) );
ops_define( 'CREATE_APPROVAL_EMAIL_ALERT', __( 'An activation email has been sent to the address you gave us.', 'core' ) );
ops_define( 'CREATE_PLAYER_SUBMIT_LABEL', __( 'Create Account', 'core' ) );
ops_define( 'BUTTON_SUBMIT', __( 'Submit', 'core' ) );

// Game Lobby Page
// -----------------------------------------------------------

ops_define( 'TABLE_HEADING_NAME', __( 'Table Name', 'core' ) );
ops_define( 'TABLE_HEADING_PLAYERS', __( 'Players', 'core' ) );
ops_define( 'TABLE_HEADING_TYPE', __( 'Table Type', 'core' ) );
ops_define( 'TABLE_HEADING_BUYIN', __( 'Buy In', 'core' ) );
ops_define( 'TABLE_HEADING_SMALL_BLINDS', __( 'Small Blinds', 'core' ) );
ops_define( 'TABLE_HEADING_BIG_BLINDS', __( 'Big Blinds', 'core' ) );
ops_define( 'TABLE_HEADING_STATUS', __( 'Table Status', 'core' ) );
ops_define( 'NEW_GAME', __( 'New Game', 'core' ) );
ops_define( 'PLAYING', __( 'Playing', 'core' ) );

// My Player & Player Rankings Pages
// -----------------------------------------------------------

ops_define( 'PLAYER_PROFILE', __( 'Player Profile', 'core' ) );
ops_define( 'PLAYER_IS_BROKE', __( 'Your Player Is Broke!!', 'core' ) );
ops_define( 'PLAYER_STATS', __( 'Statistics', 'core' ) );
ops_define( 'PLAYER_CHOOSE_AVATAR', __( 'Avatar', 'core' ) );
ops_define( 'PLAYER_CHANGE_PWD', __( 'Password', 'core' ) );
ops_define( 'PLAYER_CHANGE_SETTINGS', __( 'Settings', 'core' ) );
ops_define( 'BOX_GAME_STATS', __( 'Answer:', 'core' ) );
ops_define( 'BOX_MOVE_STATS', __( 'Administrator', 'core' ) );
ops_define( 'BOX_HAND_STATS', __( 'VIP Account', 'core' ) );
ops_define( 'BOX_FOLD_STATS', __( 'Terms & Conditions', 'core' ) );
ops_define( 'BOX_STD_AVATARS', __( 'Standard Avatars', 'core' ) );
ops_define( 'BOX_CUSTOM_AVATARS', __( 'Custom Avatars', 'core' ) );
ops_define( 'STATS_GAME', __( 'Game Statistics', 'core' ) );
ops_define( 'STATS_HAND', __( 'Hand Statistics', 'core' ) );
ops_define( 'STATS_MOVE', __( 'Move Statistics', 'core' ) );
ops_define( 'STATS_FOLD', __( 'Fold Statistics', 'core' ) );
ops_define( 'STATS_PLAYER_NAME', __( 'Player Name:', 'core' ) );
ops_define( 'STATS_PLAYER_RANKING', __( 'Player Ranking:', 'core' ) );
ops_define( 'STATS_PLAYER_CREATED', __( 'Player Created:', 'core' ) );
ops_define( 'STATS_PLAYER_BANKROLL', __( 'Bankroll:', 'core' ) );
ops_define( 'STATS_PLAYER_LOGIN', __( 'Last login:', 'core' ) );
ops_define( 'STATS_PLAYER_GAMES_PLAYED', __( 'Games Played:', 'core' ) );
ops_define( 'STATS_PLAYER_TOURNAMENTS_PLAYED', __( 'Tournaments Played:', 'core' ) );
ops_define( 'STATS_PLAYER_TOURNAMENTS_WON', __( 'Tournaments Won:', 'core' ) );
ops_define( 'STATS_PLAYER_TOURNAMENTS_RATIO', __( 'Tournament Win Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_HANDS_PLAYED', __( 'Hands Played:', 'core' ) );
ops_define( 'STATS_PLAYER_HANDS_WON', __( 'Hands Won:', 'core' ) );
ops_define( 'STATS_PLAYER_HAND_RATIO', __( 'Hand Win Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_FOLD_RATIO', __( 'Fold Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_CHECK_RATIO', __( 'Check Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_CALL_RATIO', __( 'Call Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_RAISE_RATIO', __( 'Raise Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_ALLIN_RATIO', __( 'All In Ratio:', 'core' ) );
ops_define( 'STATS_PLAYER_FOLD_PREFLOP', __( 'Fold Pre-Flop:', 'core' ) );
ops_define( 'STATS_PLAYER_FOLD_FLOP', __( 'Fold After Flop', 'core' ) );
ops_define( 'STATS_PLAYER_FOLD_TURN', __( 'Fold After Turn:', 'core' ) );
ops_define( 'STATS_PLAYER_FOLD_RIVER', __( 'Fold After River:', 'core' ) );
ops_define( 'STATS_PLAYER_OLD_PWD', __( 'Old Password:', 'core' ) );
ops_define( 'STATS_PLAYER_NEW_PWD', __( 'New Password:', 'core' ) );
ops_define( 'STATS_PLAYER_CONFIRM_PWD', __( 'Confirm Password:', 'core' ) );
ops_define( 'STATS_PLAYER_PWD_CHAR_LIMIT', __( '[ 5-10 chars ]', 'core' ) );
ops_define( 'BUTTON_STATS_PLAYER_CREDIT', __( 'Click here to renew your initial credit', 'core' ) );
ops_define( 'BUTTON_UPLOAD', __( 'Upload', 'core' ) );
ops_define( 'STATS_MSG_FILE_FORMAT', __( 'Your image must be less than or equal to 2mb!', 'core' ) );
ops_define( 'STATS_MSG_MISSING_DATA', __( 'Your image must be in jpg format!', 'core' ) );
ops_define( 'STATS_MSG_PWD_CHARS', __( 'Passwords can only contain letters and numbers.', 'core' ) );
ops_define( 'STATS_MSG_PWD_LENGTH', __( 'Your password must be 5-10 chars long.', 'core' ) );
ops_define( 'STATS_MSG_PWD_CONFIRM', __( 'Your new password and confirm fields must match.', 'core' ) );
ops_define( 'STATS_MSG_PWD_INCORRECT', __( 'Your old password was incorrect!', 'core' ) );

// Admin Panel
// -----------------------------------------------------------

ops_define( 'ADMIN_MANAGE_TABLES', __( 'Tables', 'core' ) );
ops_define( 'ADMIN_MANAGE_MEMBERS', __( 'Members', 'core' ) );
ops_define( 'ADMIN_MANAGE_ADDONS', __( 'Addons', 'core' ) );
ops_define( 'ADMIN_MANAGE_THEMES', __( 'Themes', 'core' ) );
ops_define( 'ADMIN_MANAGE_SETTINGS', __( 'Settings', 'core' ) );
ops_define( 'ADMIN_MANAGE_STYLES', __( 'Table Styles', 'core' ) );
ops_define( 'ADMIN_SETTINGS_UPDATED', __( 'Your game settings have been updated!', 'core' ) );
ops_define( 'ADMIN_GENERAL', __( 'General Settings', 'core' ) );
ops_define( 'ADMIN_SETTINGS_TITLE', __( 'Browser Page Title:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_EMAIL', __( 'Require Email Address:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_LANDLOBBY', __( 'Make Lobby Front Page:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_APPROVAL', __( 'Approval Mode:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_IPCHECK', __( 'IP Check:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_LOGIN', __( 'Bypass Login:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SESSNAME', __( 'Session Name:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_AUTODELETE', __( 'Auto Delete Players:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_ALWAYSFOLD', __( 'Allow Always Fold:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STRADDLE', __( 'Allow Straddle:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKETS', __( 'Use WebSockets:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKET_ADDR', __( 'WebSocket Address:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKET_PORT', __( 'WebSocket Port:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STAKESIZE', __( 'Server Stake Size:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_BROKE_BUTTON', __( '"Your Broke" Button:', 'core' ) );
ops_define( 'ADMIN_TIMER', __( 'Timer Settings', 'core' ) );
ops_define( 'ADMIN_SETTINGS_KICK', __( 'Kick Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_MOVE', __( 'Move Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SHOWDOWN', __( 'Showdown Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SITOUT', __( 'Sit Out Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_DISCONNECT', __( 'Disconnect Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STRADDLE_TIMER', __( 'Straddle Wait Timer:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_RAISEBUTTONS', __( 'Raise Buttons:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_TIMERLEFT', __( '5 Seconds Notification Sound:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_PLAYBFRCARDS', __( 'Allow Play Before Cards are Dealt:', 'core' ) );
ops_define( 'ADMIN_SETTINGS_TITLE_HELP', __( 'This title will appear in your web browsers page title.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_EMAIL_HELP', __( 'Select if members need to provide an email address when signing up.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_LANDLOBBY_HELP', __( 'Select if lobby should be the landing page.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_APPROVAL_HELP', __( 'Select automatic, email verification or admin approval.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_IPCHECK_HELP', __( 'Prevent multiple players with identical IP addesses playing at the same table.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_LOGIN_HELP', __( 'Switch this on if you are using your own session based login system.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SESSNAME_HELP', __( 'Your identifying session name from your own login system.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_AUTODELETE_HELP', __( 'Select if you want the system to delete inactive players.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_ALWAYSFOLD_HELP', __( 'Select Yes if you want the user to be able to fold at all times.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STRADDLE_HELP', __( 'Select Yes if you want the user to be able to straddle.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKETS_HELP', __( 'Select Yes if you want to use WebSockets for gameplay.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKET_ADDR_HELP', sprintf( __( 'WebSocket Server Address to connect to. Default is %s', 'core' ), "wss://domain.com/ws" ) );
ops_define( 'ADMIN_SETTINGS_WEBSOCKET_PORT_HELP', __( 'Port number you want to start the websocket on. Default is 80 or 443 if https is on.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STAKESIZE_HELP', __( 'Switch the server stakes size from tiny stakes to high rollers .', 'core' ) );
ops_define( 'ADMIN_SETTINGS_BROKE_BUTTON_HELP', __( '"Turn on/off "Your Broke" module and initial free game stake.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_KICK_HELP', __( 'Controls kicking players repeatedly failing to take their turn.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_MOVE_HELP', __( 'Controls the time a player has to make their move.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SHOWDOWN_HELP', __( 'Controls the time a showdown hand will be displayed for.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_SITOUT_HELP', __( 'Controls the length of stay on the sit out page.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_DISCONNECT_HELP', __( 'Controls the time before kicking disconnected players.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_STRADDLE_TIMER_HELP', __( 'Controls the time a player has to straddle or skip.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_RAISEBUTTONS_HELP', __( 'Raise buttons to show to user during gameplay', 'core' ) );
ops_define( 'ADMIN_SETTINGS_TIMERLEFT_HELP', __( 'Choose whether you want to play a notification sound when the player has 5 seconds to make a move.', 'core' ) );
ops_define( 'ADMIN_SETTINGS_PLAYBFRCARDS_HELP', __( 'Choose whether to allow the player to buy in the current running game before the cards are dealt.', 'core' ) );
ops_define( 'BUTTON_SAVE_SETTINGS', __( 'Save Settings', 'core' ) );
ops_define( 'ADMIN_MEMBERS_NAME', __( 'Player', 'core' ) );
ops_define( 'ADMIN_MEMBERS_RANK', __( 'Rank', 'core' ) );
ops_define( 'ADMIN_MEMBERS_EMAIL', __( 'Email', 'core' ) );
ops_define( 'ADMIN_MEMBERS_CREATED', __( 'Created', 'core' ) );
ops_define( 'ADMIN_MEMBERS_IPADDRESS', __( 'IP Address', 'core' ) );
ops_define( 'ADMIN_MEMBERS_APPROVE', __( 'Status', 'core' ) );
ops_define( 'ADMIN_MEMBERS_BAN', __( 'Ban', 'core' ) );
ops_define( 'ADMIN_MEMBERS_DELETE', __( 'Delete', 'core' ) );
ops_define( 'ADMIN_MEMBERS_RESET_STATS', __( 'Stats', 'core' ) );
ops_define( 'BUTTON_APPROVE', __( 'Approve', 'core' ) );
ops_define( 'BUTTON_BAN', __( 'Ban', 'core' ) );
ops_define( 'BUTTON_UNBAN', __( 'Unban', 'core' ) );
ops_define( 'BUTTON_DELETE', __( 'Delete', 'core' ) );
ops_define( 'BUTTON_RESET', __( 'Reset', 'core' ) );
ops_define( 'BUTTON_CREATE_TABLE', __( 'Create Table', 'core' ) );
ops_define( 'BUTTON_INSTALL', __( 'Install', 'core' ) );
ops_define( 'ADMIN_TABLES_NAME', __( 'Table Name', 'core' ) );
ops_define( 'ADMIN_TABLES_TYPE', __( 'Table Type', 'core' ) );
ops_define( 'ADMIN_TABLES_GAME', __( 'Game Style', 'core' ) );
ops_define( 'ADMIN_TABLES_MIN', __( 'Minimum Buyin', 'core' ) );
ops_define( 'ADMIN_TABLES_MAX', __( 'Maximum Buyin', 'core' ) );
ops_define( 'ADMIN_TABLES_STYLE', __( 'Table Style', 'core' ) );
ops_define( 'ADMIN_TABLES_DELETE', __( 'Delete', 'core' ) );
ops_define( 'ADMIN_TABLES_OPTIONS', __( 'Options', 'core' ) );
ops_define( 'ADMIN_TABLES_BB', __( 'Big blind', 'core' ) );
ops_define( 'ADMIN_TABLES_SB', __( 'Small blind', 'core' ) );
ops_define( 'BUTTON_SAVE', __( 'Save', 'core' ) );
ops_define( 'BUTTON_BACK', __( 'Back', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_TICKET', __( 'Ticket value', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_RAKE', __( 'Rake value', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_PRIZE_1', __( 'Prize 1', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_PRIZE_2', __( 'Prize 2', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_PRIZE_3', __( 'Prize 3', 'core' ) );
ops_define( 'ADMIN_TOURNAMENT_START_DATE', __( 'Start date and time', 'core' ) );
ops_define( 'ADMIN_STYLES_INSTALLED', __( 'Installed Table Styles', 'core' ) );
ops_define( 'ADMIN_STYLES_PREVIEW', __( 'Style Preview', 'core' ) );
ops_define( 'ADMIN_STYLES_NEW_NAME', __( 'New Style Name', 'core' ) );
ops_define( 'ADMIN_STYLES_CODE', __( 'Validation Code', 'core' ) );
ops_define( 'ADMIN_MSG_STYLE_INSTALLED', __( 'This style has already been installed!', 'core' ) );
ops_define( 'ADMIN_MSG_MISSING_DATA', __( 'Missing data! Please try again.', 'core' ) );
ops_define( 'ADMIN_MSG_INVALID_CODE', __( 'Invalid style name or license code!', 'core' ) );

// Poker Game Language
// -----------------------------------------------------------

ops_define( 'GAME_LOADING', __( 'Loading...', 'core' ) );
ops_define( 'GAME_PLAYER_BUYS_IN', __( 'buys in for', 'core' ) );
ops_define( 'INSUFFICIENT_BANKROLL_SITNGO', __( 'Your bank roll is not high enough to play on this table!', 'core' ) );
ops_define( 'INSUFFICIENT_BANKROLL_TOURNAMENT', __( 'Your bank roll is not high enough to play in this tournament!', 'core' ) );
ops_define( 'GAME_STARTING', __( 'game starting...', 'core' ) );
ops_define( 'GAME_PLAYER_FOLDS', __( 'folds', 'core' ) );
ops_define( 'GAME_PLAYER_CALLS', __( 'calls', 'core' ) );
ops_define( 'GAME_PLAYER_CHECKS', __( 'checks', 'core' ) );
ops_define( 'GAME_PLAYER_RAISES', __( 'raises', 'core' ) );
ops_define( 'GAME_PLAYER_GOES_ALLIN', __( 'goes all in!', 'core' ) );
ops_define( 'GAME_PLAYER_POT', __( 'POT:', 'core' ) );
ops_define( 'GAME_MSG_WON_TOURNAMENT', __( 'won the last tournament', 'core' ) );
ops_define( 'GAME_MSG_LOST_CONNECTION', __( 'has lost connection and leaves the table', 'core' ) );
ops_define( 'GAME_MSG_PLAYER_BUSTED', __( 'has busted and leaves the table', 'core' ) );
ops_define( 'GAME_MSG_PLAYERS_JOINING', __( 'players joining...', 'core' ) );
ops_define( 'GAME_MSG_LETS_GO', __( 'Lets go!', 'core' ) );
ops_define( 'GAME_MSG_CHIP_LEADER', __( 'is chip leader', 'core' ) );
ops_define( 'GAME_MSG_DEALER_BUTTON', __( 'has the dealer button', 'core' ) );
ops_define( 'GAME_MSG_DEAL_CARDS', __( 'Dealing the holecards...', 'core' ) );
ops_define( 'GAME_MSG_DEAL_FLOP', __( 'Dealing the flop...', 'core' ) );
ops_define( 'GAME_MSG_DEAL_TURN', __( 'Dealing the turn...', 'core' ) );
ops_define( 'GAME_MSG_SHOWDOWN', __( 'SHOWDOWN!', 'core' ) );
ops_define( 'GAME_MSG_ALLFOLD', __( 'wins, everyone folded', 'core' ) );
ops_define( 'GAME_MSG_PLAYER_ALLIN', __( 'is all in', 'core' ) );
ops_define( 'GAME_MSG_DEAL_RIVER', __( 'Dealing the river...', 'core' ) );
ops_define( 'GAME_MSG_SMALL_BLIND', __( 'posts small blind', 'core' ) );
ops_define( 'GAME_MSG_BIG_BLIND', __( 'posts big blind', 'core' ) );
ops_define( 'GAME_MSG_SPLIT_POT', __( 'split pot', 'core' ) );
ops_define( 'GAME_MSG_SPLIT_POT_RESULT', __( 'The pot is split between the players who have the best', 'core' ) );
ops_define( 'GAME_MSG_WINNING_HAND', __( 'Winning hand:', 'core' ) );
ops_define( 'GAME_MSG_PROCESSING', __( 'Processing...', 'core' ) );
ops_define( 'BUTTON_START', __( 'Start Game', 'core' ) );
ops_define( 'BUTTON_CALL', __( 'Call', 'core' ) );
ops_define( 'BUTTON_CHECK', __( 'Check', 'core' ) );
ops_define( 'BUTTON_FOLD', __( 'Fold', 'core' ) );
ops_define( 'BUTTON_REBUY', __( 'Rebuy', 'core' ) );
ops_define( 'BUTTON_STRADDLE', __( 'Straddle!', 'core' ) );
ops_define( 'BUTTON_BET', __( 'Bet', 'core' ) );
ops_define( 'BUTTON_ALLIN', __( 'All In', 'core' ) );
ops_define( 'WIN_PAIR', __( 'pair of', 'core' ) ); // e.g. user wins with a pair of 9's
ops_define( 'WIN_2PAIR', __( '2 pair', 'core' ) ); // e.g. user wins 2 pair 3's & 8's
ops_define( 'WIN_FULLHOUSE', __( 'full house', 'core' ) ); // e.g. user wins with a full house
ops_define( 'WIN_SETOF3', __( 'a set of', 'core' ) ); // e.g. user wins with a set of 3's
ops_define( 'WIN_SETOF4', __( 'all the', 'core' ) ); // e.g. user wins with all the J's
ops_define( 'WIN_FLUSH', __( 'high flush', 'core' ) ); // e.g. user wins with a K high flush
ops_define( 'WIN_STRAIGHT_FLUSH', __( 'straight flush', 'core' ) ); // e.g. user wins with a K high straight flush
ops_define( 'WIN_ROYALFLUSH', __( 'royal flush', 'core' ) ); // e.g. user wins with a royal flush
ops_define( 'WIN_STRAIGHT', __( 'high straight', 'core' ) ); // e.g. user wins with a J high straight
ops_define( 'WIN_LOW_STRAIGHT', __( 'low straight', 'core' ) ); // e.g. user wins with a low straight
ops_define( 'WIN_HIGHCARD', __( 'highcard', 'core' ) ); // e.g. user wins with a k highcard
ops_define( 'BUTTON_SMALLSCREEN', __( 'Small Screen', 'core' ) ); // Switch to Small Screen
ops_define( 'BUTTON_WIDESCREEN', __( 'Wide Screen', 'core' ) ); // Switch to Wide Screen
ops_define( 'GAME_TEXAS', __( "Texas Hold em", 'core' ) );
ops_define( 'GAME_OMAHA', __( "Omaha Hold em", 'core' ) );
ops_define( 'MONEY_PREFIX_LABEL', __( 'Money Prefix:', 'core' ) );
ops_define( 'MONEY_PREFIX_LABEL_HELP', __( 'The character that appears before the pot/bet number', 'core' ) );
ops_define( 'MONEY_DECIMAL_LABEL', __( 'Money Decimal:', 'core' ) );
ops_define( 'MONEY_DECIMAL_LABEL_HELP', __( 'The decimal character for money', 'core' ) );
ops_define( 'MONEY_THOUSAND_LABEL', __( 'Money Thousand:', 'core' ) );
ops_define( 'MONEY_THOUSAND_LABEL_HELP', __( 'The decimal character for each thousand iteration of money', 'core' ) );
ops_define( 'ADMIN_USERS_LABEL', __( 'Admin Users', 'core' ) );
ops_define( 'ADMIN_USERS_LABEL_HELP', __( 'Usernames of admins (separated by comma)', 'core' ) );
ops_define( 'REG_WINPOT_LABEL', __( 'Registration Chips', 'core' ) );
ops_define( 'REG_WINPOT_LABEL_HELP', __( 'The amount of chips a user gets after creating a new account.', 'core' ) );

function ops_define($label, $definition)
{
	if ( !isset($GLOBALS['lang']) )
		$GLOBALS['lang'] = array();
	
	$GLOBALS['lang'][strtolower($label)] = $definition;

	if (! defined($label))
	{
		define($label, $definition);
		return true;
	}

	return false;
}

$opsTheme->addVariable('lang', $lang);
?>