/*=====================================*\
|| ################################### ||
|| # Nick Changer        version 0.1 # ||
|| ################################### ||
\*=====================================*/

function nick_changer_check_availability(username)
{
	do_nick_change = new vB_AJAX_Handler(true);
	do_nick_change.username = username;
	do_nick_change.onreadystatechange(nick_changer_check_availability_done);
	do_nick_change.send('nick_changer.php?do=checkavailability&using_ajax=1&username=' + username);

	fetch_object('nick_changer_available').innerHTML = "";
	fetch_object('nick_changer_spinner').style.display = '';
}

function nick_changer_check_availability_done()
{
	fetch_object('nick_changer_spinner').style.display = 'none';

	if (do_nick_change.handler.readyState == 4 && do_nick_change.handler.status == 200)
	{
		fetch_object('nick_changer_available').innerHTML = do_nick_change.handler.responseText;
	}
}