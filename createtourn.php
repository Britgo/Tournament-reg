<?php

// *****************************************************************************
// PLEASE BE CAREFUL ABOUT EDITING THIS FILE, IT IS SOURCE-CONTROLLED BY GIT!!!!
// Your changes may be lost or break things if you don't do it correctly!
// *****************************************************************************

//   This program is free software: you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.

//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.

//   You should have received a copy of the GNU General Public License
//   along with this program.  If not, see <http://www.gnu.org/licenses/>.

include 'php/session.php';
include 'php/checklogged.php';
include 'php/opendb.php';
include 'php/club.php';
include 'php/country.php';
include 'php/rank.php';
include 'php/person.php';
include 'php/player.php';
include 'php/tdate.php';
include 'php/tournclass.php';

// Check the guy can create tournaments before we go any further

if (!$organ)  {
	$mess = 'Not Tournament Organiser';
	include 'php/wrongentry.php';
	exit(0);
}

try {
	opendb();
	$orguser = new Player();
	$orguser->fromid($userid);
}
catch (Tcerror $e)  {
	$mess = 'Cannot open database or find user';
	include 'php/wrongentry.php';
	exit(0);
}

$tourn = new Tournament();
$tourn->Orguser = $userid;

$Title = 'Create a new tournament';
include 'php/head.php';
?>
<body>
<script language="javascript" src="webfn.js"></script>
<script language="javascript">
<?php
//  Set up list of existing codes to check against
	
print "Existing_codes = new Array();\n";
$codes = Tournament::get_tcodes();
foreach ($codes as $code)
	print "Existing_codes['$code'] = 1;\n";
?>

function checkform() {
	var fm = document.ctform;
 	var tcode = fm.tcode.value;
  	if  (!nonblank(tcode))  {
 	 	alert("Please give a tournament code (e.g. anytown2014)");
     	return false;
   }
	if  (!okcode(tcode))  {
		alert("Please give a meaningful tournament code letters/digits");
		return false;
	}
	if  (/editme/i.test(tcode))  {
		alert("Please change the tournament code from EDITME");
		return false;
	}
	if (Existing_codes[tcode]) {
     	alert("Already have a tournament called " + tcode);
     	return false;
   }
  	if  (!nonblank(fm.tname.value))  {
		alert("Please give a tournament name");
		return false;
	}
   if (!nonblank(fm.address.value)) {
		alert("Please give a venue");
		return false;
   }
   if (!nonblank(fm.postcode.value))  {
		alert("Please give a postcode");
		return  false;
   }
   if (!nonblank(fm.contact.value)) {
   	alert("Please give contact name");
   	return  false;
   }
   if (!nonblank(fm.email.value)) {
   	alert("Please give contact email");
   	return  false;
   }
   try {
   	if  (isprice(fm.fee, "fee") <= 3.0)
   		throw Error("Invalid fee - too low");	
   	isprice(fm.lunch, "lunch");
   	isprice(fm.nonbga, "non-BGA");
		isprice(fm.concess1, "Concession 1");
		isprice(fm.concess2, "Concession 2");
   	isprice(fm.latefee, "late fee");
   	var tdat = datecheck(fm.year, fm.month, fm.day, "Start date");
   	if  (isprice(fm.ebird, "Early bird") != 0.0)  {
   		var ebdat = datecheck(fm.ebyear, fm.ebmonth, fm.ebday, "Early Bird date");
   		if  (ebdat > tdat - 604800000)
   			throw Error("Early bird date should be earlier!");
   	}
 		if (tdat.getDay() > 0  &&  tdat.getDay() < 5  &&  !confirm("Not a weekend - OK"))
			return false;
	}
	catch (err)  {
		alert(err.message);
		return false;
	}
	return true;
}
</script>
<?php include 'php/nav.php'; ?>
<h1>Create a new tournament</h1>
<p>Please enter the required details in the form below.</p>
<p>You may find it easier to "clone" a similar previous tournament and then amend the details rather than entering everything here.</p>
<form name="ctform" action="ct2.php" method="post" enctype="application/x-www-form-urlencoded" onsubmit="javascript:return checkform();">
<table cellpadding="5" cellspacing="5" align="left" width="800"  summary="Tournament Table">
<tr>
	<td colspan="2">Please enter a unique single-word code for the tournament here.<br />This should be letters and digits only.</td>
</tr>
<tr>
	<td><b>Tournament Code</b></td>
<?php
$dat = getdate();
$year = $dat['year'];
$tcd = "EDITME$year";
print <<<EOT
	<td><input type="text" name="tcode" size="20" value="$tcd"></td>
</tr>

EOT;
if ($admin) {
	print <<<EOT
<tr>
	<td><b>Organiser userid</b></td>
	<td><input type="text" name="organiser" value="$userid" size="16"></td>
</tr>

EOT;
}
else
	$tourn->hidden_organiser();
?>
<tr>
	<td colspan="2">Please give a full name for the tournament, usually just location and year.<br />
	This is normally displayed as a heading in various places.</td>
</tr>
<tr>
	<td><b>Tournament Name</b></td>
	<td><input type="text" name="tname" size="40"></td>
</tr>
<tr>
	<td colspan="2">This is the class of the tournament, A B or C. If this is not a class which
	affects ratings, select N.</td>
</tr>
<tr>
	<td><b>Tournament Class</b></td>
	<td>
		<input type="radio" name="tclass" value="A" />A
		<input type="radio" name="tclass" value="B" />B
		<input type="radio" name="tclass" value="C" />C
		<input type="radio" name="tclass" value="N" checked="checked" />N
	</td>
</tr>
<tr>
	<td><b>Format</b><br />Please give a brief description of the tournament parameters with time limits.
	Say what the rules are if not AGA and 7.5 Komi.</td>
	<td><textarea name="format" rows="6" cols="50"></textarea></td>
</tr>
<tr>
	<td><b>Overview</b><br />Please give a brief description of the tournament timetable. Mention any side
	tournaments e.g. 13x13, quiz etc.</td>
	<td><textarea name="overview" rows="6" cols="50"></textarea></td>
</tr>
<tr>
	<td><b>Venue</b><br />Please give a description of the venue, with street address.</td>
	<td><textarea name="address" rows="6" cols="50"></textarea></td>
</tr>
<tr>
	<td colspan="2">Please give the correct postcode of the address for the benefit of Sat Nav users.</td>
</tr>
<tr>
	<td><b>Postcode</b></td>
	<td><input type="text" name="postcode" size="10"></td>
</tr>
<tr>
	<td><b>Provisional dates and details</b></td>
	<td><input type="checkbox" name="provisional"$pprov /></td>
</tr>
<tr>
	<td><b>Open for entries</b></td>
	<td><input type="checkbox" name="open"$popen /></td>
</tr>
<tr>
	<td><b>Date (or first day)</b></td>
	<td><?php $tourn->Sdate->dateopt(); ?></td>
</tr>
<tr>
	<td><b>Number of days</b></td>
	<td><select name="ndays">
	<option selected="selected">1</option>
<?php
for ($i = 2;  $i <= 7;  $i++) print "<option>$i</option>\n";
?></td></tr>
<tr>
	<td><b>Number of rounds</b></td>
	<td><select name="rounds">
	<option selected="selected">3</option>
	<?php
	for ($i = 4;  $i <= 20;  $i++) print "<option>$i</option>\n";
?></td></tr>
<tr>
	<td><b>Basic fee for tournament</b></td>
	<td><input type="text" name="fee" value="5.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td><b>Supplement for lunch, put zero if no lunch</b></td>
	<td><input type="text" name="lunch" value="0.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td><b>Dinner or other event, blank if none</b></td>
	<td><input type="text" name="dinner" size="20" /></td>
</tr>
<tr>
	<td><b>Supplement if not BGA member</b></td>
	<td><input type="text" name="nonbga" value="3.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td colspan="2">The following two fields are concession amounts.
	Typically entrants are either standard, or concessionary if retired or students.
	This allows up to two alternative concession discounts, plus descriptions to be assigned.
	Just set one to zero to turn it off.</td>
<tr>
	<td><b>Concession 1 amount</b></td>
	<td><input type="text" name="concess1" value="3.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td>C<b>oncession 1 description</b></td>
	<td><input type="text" name="concess1name" value="Concession" size="20" /></td>
</tr>
<tr>
	<td><b>Concession 2 amount</b></td>
	<td><input type="text" name="concess2" value="0.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td><b>Concession 2 description</b></td>
	<td><input type="text" name="concess2name" value="Other concession" size="20" /></td>
</tr>
<tr>
	<td><b>Late entry fee</b></td>
	<td><input type="text" name="latefee" value="5.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td><b>Late entry applies if booked</b></td>
	<td><select name="latedays"><option value="0" selected="selected">On Day</option>
	<option value="1">Previous day</option>
<?php
for ($i = 2;  $i <= 20;  $i++)  {
	$lab = "$i days before";
	print "<option value=\"$i\">$lab</option>\n";
}
?></select></td></tr>
<tr>
	<td><b>Early bird discount</b></td>
	<td><input type="text" name="ebird" value="0.00" size="6" maxlength="6" /></td>
</tr>
<tr>
	<td><b>Last day for early bird</b></td>
	<td><?php $tourn->Ebdate->dateopt("eb"); ?></td>
<tr>
	<td><b>Contact</b></td>
	<td>
<?php
print <<<EOT
<input type="text" name="contact" value="{$orguser->display_name()}" size="30"></td></tr>

EOT;
?>
<tr>
	<td><b>Contact email</b></td>
	<td>
<?php
print <<<EOT
<input type="text" name="email" value="{$orguser->display_email_nolink()}" size="30"></td></tr>

EOT;
?>
<tr>
	<td><b>Website</b></td>
	<td><input type="text" name="website" size="40"></td>
</tr>
<?php include 'php/sumchallenge.php'; ?>
<tr>
	<td><b>Click to create</b></td>
	<td><input type="submit" value="Create tournament"></td>
</tr>
</table>
</form>
</div>
</div>
</body>
</html>
