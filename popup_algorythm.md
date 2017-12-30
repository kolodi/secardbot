###Commands:
start -  Create new tournament
help - Display help
new_popup - Create a popup
start_popup - Begin popup
cancel_popup - Cancel and delete popup
popups - List all active popups
join_popup - Join popup
quit_popup - Quit popup
kick - Kick a partecipant
participants - List of partecipants
opponent - Display you current opponent
popup_results - Display a popup statistics
report_score - Report match results
confirm_score - Confirm score reported by your opponent

###Details:
/new_popup <Name> - initiate new popup with an unique name, promped if omitted. Can do only if no current popup in place (handle multiple popups in the future)
/start_popup <Name> - shuffle partecipants, create matches and return stats. At least 4 partecipants should be registered. Only creator can do.
/cancel_popup <Name> - cancel current popup. Display warning message if there is at least one partecipant, plus enter word "Delete". Only creator or chat admin can do.
/join_popup <IGN> (specify In Game Name)
/quit_popup (display confirm, if popup is already started display warning about dropping from active popup)
/kick - display list of buttons with names, press a button to kick partecipant (only creator can do)
/participants - return a list of partecipants
/opponent - display you current opponent username, name and IGN (after popup started)
/popup_results <Name> - display current popup statistics, or finished one 
/report_score <[your_score]-[opponets_score]> - Report match results, example: 2 - 1. Return formatted result and  /confirmScore command for opponent to confirm
/confirm_score - confirm score reported by your opponent, error returned when no score was reported by your opponent, next opponent info for both partecipants returned (or for a winner only if playoff)
