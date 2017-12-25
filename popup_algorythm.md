Commands:
/newPopup <Name> - initiate new popup with an unique name, promped if omitted. Can do only if no current popup in place (handle multiple popups in the future)
/startPopup <Name> - shuffle partecipants, create matches and return stats. At least 4 partecipants should be registered. Only creator can do.
/cancelPopup <Name> - cancel current popup. Display warning message if there is at least one partecipant, plus enter word "Delete". Only creator or chat admin can do.
/joinPopup <IGN> (specify In Game Name)
/quitPopup (display confirm, if popup is already started display warning about dropping from active popup)
/kickFromPopup - display list of buttons with names, press a button to kick partecipant (only creator can do)
/partecipants - return a list of partecipants
/myOpponent - display you current opponent username, name and IGN (after popup started)
/popupResults <Name> - display current popup statistics, or finished one 
/reportScore <[your_score]-[opponets_score]> - Report match results, example: 2 - 1. Return formatted result and  /confirmScore command for opponent to confirm
/confirmScore - confirm score reported by your opponent, error returned when no score was reported by your opponent, next opponent info for both partecipants returned (or for a winner only if playoff)
