#! c:\perl\bin\perl

use strict;
use DBI;
use Digest::MD5  qw(md5 md5_hex md5_base64);

$| = 1;

# database settings
my $MYSQL        = 1;
my $DSN          = 'rodeo';
my $host         = 'localhost' ; # 'mysql';
my $opt_user     = 'rodeo_admin';         # 'rodeo';
my $opt_password = '' ;          # z:eyyzPNvEX3arHv
my $RELPATH = '../temps';
my $superuser_password = 'superman';


my $MONTH_REPORTS = 0;
my $FIRST_YEAR    = 2005;
my $TIME_LIMIT    = 800;

my $scriptname = "rodeo.pl";

#my $FOOTER = "CBL Time 0.6 <a href=\"mailto:gda\@cbl-consulting.com\">GDA</A>";
my $FOOTER = "";

my($AccessRight,
	$buffer, $dayweek,
	$login, $loginOK, $logout,
	$validerings_fel,
	$WRONG, $PRINTHEADER, $sth, $dbh, $SQL, $user, $admin, $visible,
	$provision, $showtidbank, $PROVISION, $username, $login_message,
	%EmpNames, @ProjLeaders, %lopnr_namn,
  %inloggad, %arvode, @emps);


my %titles = (0=>'Salarié', 1=>'Responsable du Projet', 2=>'Responsable facturation' );

#my @dayOfJan1 = (6,1,2,3,1,3,4);   # 2000,..,2006
#my @dayOfJan1 = (6,4,3,2,1,6,5,4,3);   # 2000,..,2006,2007,2008 <- num du 1er jeudi de janvier
#my @dayOfJan1 = (6,4,3,2,1,6,5,4,3,1,7);   # 2000,..,2008,2009,2010 <- num du 1er jeudi de janvier
#my @dayOfJan1 = (6,4,3,2,1,6,5,4,3,1,7,6);   # 2000,..,2011 <- num du 1er jeudi de janvier
#my @dayOfJan1 = (6,4,3,2,1,6,5,4,3,1,7,6,5);   # 2000,..,2012 <- num du 1er jeudi de janvier
 my @dayOfJan1 = (6,4,3,2,1,6,5,4,3,1,7,6,5,3,2,1);   # 2000,..,2015 <- num du 1er jeudi de janvier

my $first_monday = 3;
my $hours_a_day = 8;
#my $row_number = 12;
#my $row_number = 18;
#my $row_number = 24;
my $row_number = 40;
my @Days = ('','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche');
my @months = (0,31,28+0,31,30,31,30,31,31,30,31,30,31);
my @Months_W = ('- Mois -','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
my @tidkoder = ('Normal', 'Extra1', 'Extra2', 'Non Facturé', 'Extra1 N.Fac.', 'Extra2 N.Fac.');

my @absent_vals = ('CP', 'Maladie', 'RTT', 'Ecole Terry', 'Sans solde', 'Absence payée', 'Mi-temps flexible', 'Komptid' );
my @absent_keys = (501..507);

&SQL_Connect;

my @times = localtime(time);
$times[4]++;        # januari är månad 0
$times[5] += 1900;  # år räknas från 1900

my $lastyear = $times[5];
my $dayweek = $times[7];

my $lastmonth = $times[4];

my $lastweek = int( ($dayweek - $times[4] +  $dayOfJan1[$lastyear - 2000] ) / 7) + 1;

#$lastweek++ if ($dayweek % 7 != 0);
$lastweek ||= 1;

my $expDate = "09-Nov-2002 00:00:00 GMT";
my $domain = "www4.aname.net";
my $path = "/~siteteam/cgi-bin/";
my $salt = 'tHe sALt 0f tHE eARtH';

my $abstime = ($times[7]*24 + $times[2])*60 + $times[1];

my %qform;
my %form;

my @pairs = split( /&/, $ENV{'QUERY_STRING'} );
foreach  ( @pairs ) {
  my( $name, $value ) = split /=/;
  $value =~ tr/+/ /;
  $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C",hex($1))/eg;
  $qform{ $name } = $value;
}

my $visa_year = $qform{'visayear'};

my %cookies = &getCookies;

# $IP = $ENV{'REMOTE_ADDR'};

my $hoppa_inloggning;

read( STDIN, $buffer, $ENV{ 'CONTENT_LENGTH' } );
@pairs = split( /&/, $buffer );
foreach ( @pairs ) {
  my( $name, $value ) = split /=/;
  $value =~ tr/+/ /;
  $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C",hex($1))/eg;
  $form{ $name } = $value;
}

if ( my $login = $qform{'login'} ) {
  my($userpass, $digest, $role, $firstname, $lastname);
  my $passwd = $form{'passwd'};
  $passwd = crypt($passwd, "saltet är gott");
  $user   = $form{'user'};
  if ( !$user ) {
  	$hoppa_inloggning = 1;
  	goto HOPPA_INLOGGNING;
  }

  if ($user != 666) {
    $SQL = qq|Select password, authority, name, surname
              From users
              Where (userid = $user)|;
    &SQL_Execute($SQL);
    ($userpass, $AccessRight, $firstname, $lastname) = &SQL_Fetch();
  }
  else {
    $userpass = crypt($superuser_password, "saltet är gott");
    $AccessRight = 2;
  }
  if ($userpass eq $passwd) {
    if ( $login eq 'admin' ) { $role = $AccessRight; }
    else                     { $role = $AccessRight = 0; }

    &setCookie("user", $user,  $expDate, $path, $domain);
    &setCookie("role", $role,  $expDate, $path, $domain);
    &setCookie("issued", $abstime,  $expDate, $path, $domain);

    $digest = md5_base64( "$user$role$abstime$salt" );
    &setCookie("digest", $digest,  $expDate, $path, $domain);

    $username = "$firstname $lastname";

    &setCookie("name", $username,  $expDate, $path, $domain);

    $SQL = qq|Update users
              Set loggedin = $abstime
              Where userid = $user|;
    &SQL_Execute($SQL);

    $loginOK = 1;
  }
  else {
    $WRONG = $user;
  }
#  $week = $lastweek;
}

if ( $logout = $qform{'logout'} ) {
  &setCookie("user", '',  $expDate, $path, $domain);
  &setCookie("role", '',  $expDate, $path, $domain);
}

&PrintHeader;

#print "[$http_cookie]";

if ( !$loginOK ) {
# Kolla behörighet
HOPPA_INLOGGNING:
	if ($hoppa_inloggning) {
		&PrintHeader;
	}
  if ( $logout || !&Valid ) {

    if    ( $logout )        { $login_message = "Vous êtes déconnectés. A bientôt"; }
    elsif ($validerings_fel) { $login_message =  "$validerings_fel"; }

    &PrintLogin;
    &SQL_Close;

    print "</td></tr></table>\n";
    print "</body></html>\n";
    exit;
  }

  $AccessRight = $cookies{'role'};
  $user = $admin = $cookies{ 'user' };
}


########################
&MainSwitch;


&SQL_Close;

##########################################################################################
sub TidbankVecka($$$$$$) {
my($i);

my ($user, $week, $year, $cur_year, $begindate)	= @_;

my $firsttime = 0;
my ($lastfak, $kommentar, $startweek, $tidbank, $tweek, $tyear);

my $firstyear = int substr($begindate,0,4);

# kolla min max på innevarande år ($lastyear), styr sedan lastfak
$SQL = qq|Select Max(year*100+week)
          From weeks
          Where userid = $user|;
&SQL_Execute($SQL);
my $lastfak = &SQL_Fetch + 0;   # plussa på nolla för att göra typecast till int
# lastfak är den senast slutrapporterade veckan i weeks_yyyy

if ( !$lastfak ) {   # allra första gången han använder systemet
  my $month = int( substr($begindate,4,2) );
  my $day   = int( substr($begindate,6,2) );
  my $days;
  for ($i=1; $i<$month ;$i++) {
    $days += $months[$i];
  }
  $days += $day;
  $startweek = int( ($days + $dayOfJan1[$lastyear - 2000])/7  + 1);

  $tyear = int( substr($begindate,0,4) );
  $tweek = $startweek-1;

  $lastfak = $startweek - 1;
  if ( $week==$startweek && $year==$firstyear ) {
    $kommentar = "<b><font color=red>Votre première semaine dans le système</font></b><br>";
    $firsttime = 1;
  }
  else {
    $kommentar = qq|<b>Votre date de début est $begindate<br>Compléter la semaine <a class="a3" href="$scriptname?gotow=1&week=$startweek&year=$cur_year">$startweek</a></b><br>|;
  }
}
else {
	$tyear = int($lastfak/100);     # sista slutrapporterade vecka/år
	$tweek = $lastfak % 100;
	if ($tyear == $year) {

	  $SQL = qq|Select timebank
	            From weeks
	            Where (userid = $user) And (week = $week - 1) AND (year = $tyear)|;
	  &SQL_Execute($SQL);
	  $tidbank = &SQL_Fetch();

		# om senast färdigrapporterade vecka ligger mer än en vecka bak i
		# tiden summeras gällande tidbank fram
		if ($week > $tweek+1) {
		  $SQL = qq|Select Sum(hours)
		            From workhours_$year
		            Where (week > $tweek) And (week < $week)|;
		  &SQL_Execute($SQL);
		  my $mellanarbetstid = &SQL_Fetch();

		  $SQL = qq|Select Sum(monday+tuesday+wednesday+thursday+friday+saturday+sunday)
		            From reports_$year
		            Where (userid = $user) And (week > $tweek) And (week < $tweek)|;
		  &SQL_Execute($SQL);
		  my $mellantimmar = &SQL_Fetch();

		  my $mellanbank = $mellantimmar - $mellanarbetstid;
		  $tidbank += $mellanbank;
		}

	} elsif ($tyear < $year) {

 		$SQL = qq|Select timebank
	         		From weeks
	         		Where (userid = $user) And (week = $tweek) AND (year = $tyear)|;
		&SQL_Execute($SQL);
		$tidbank = &SQL_Fetch();
		$kommentar .= "<b>Previous year</b><br>";

    # Remettre les compteurs à 0		
    $tyear = $year;
		$tweek = 0;
	}

	if ($year > $cur_year){
		$kommentar .= "<b>Next year</b><br>";
	}
}

return ($tyear, $tweek, $tidbank, $kommentar, $startweek, $firstyear, $firsttime);

}

##########################################################################################
sub TidRapportera {

my $spara    = $qform{'spara'};
my $gotow    = $qform{'gotow'};

my($veckokommentar, $msg);

if ($spara) {
  &SparaVecka;
}

my $year = $form{'year'} || $qform{'year'} || $lastyear;
my $week = $form{'week'} || $qform{'week'} || $lastweek;

# säkerhet
$year = $FIRST_YEAR if ($year < $FIRST_YEAR);
$year = $lastyear+1 if ($year > $lastyear+1);
$week = 1  if ($week < 1);
$week = 52 if ($week > 52);

$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår Bisextile

# hämta den anställdes anställningsdatum (begindate). Används för år-selecten
# samt även om han inte rapporterat innan, i vilket fall även starttidbanken används
$SQL = qq|Select begindate, timebank, name, surname, current_year
          From users
          Where userid = $user|;
&SQL_Execute($SQL);
my($begindate, $tidbank, $name, $surname, $cur_year) = &SQL_Fetch;

$name .= "&nbsp;$surname";
my($tyear, $tweek, $tidbank, $tidbank_kommentar, $startweek, $firstyear, $firsttime) =
	&TidbankVecka($user, $week, $year, $cur_year, $begindate);

#print "($tyear, $tweek, $tidbank, $tidbank_kommentar, $startweek, $firstyear, $firsttime)	";

$veckokommentar .= $tidbank_kommentar;

# skriv ut röda dagar med röd färg
$SQL = qq|Select freedays, hours, comment
          From workhours_$year
          Where week = $week|;
&SQL_Execute($SQL);
my($red, $arbetstid, $komm)= &SQL_Fetch();
my @reddays = split(',',$red);
my @red_day = ('','','','','','');
foreach (@reddays) {
  $red_day[ $_ ] = 'style="background-color: red"';
}
$veckokommentar .= $komm;

# om tidbank har justerats föregående vecka, så skriv ut i kommentarsfält
my %tidtyp = (1=>'Kompledighet', 2=>'Lön');
$SQL = qq|Select original, changed, withdrawal, comment
          From timebank
          Where userid = $user And (week = $week-1) And year = $year|;
&SQL_Execute($SQL);
if ( my ($orig, $changed, $uttag, $kommentar) = &SQL_Fetch ) {
  if ($veckokommentar) { $veckokommentar .= "<br><br>"; }
  $veckokommentar .= "<b>Tidbank justerad:</b><br>$orig -> $changed tim, uttag: $tidtyp{$uttag}<br>$kommentar";
}

# plocka ut timmar raporterade veckan
$SQL = qq|Select row,projid,actid,timecode,monday,tuesday,wednesday,thursday,friday,saturday,sunday,theme,commentaire
          From reports_$year
          Where (week = $week) And (userid = $user)
          Order By row|;
&SQL_Execute($SQL);
my(@rader, $antal_rader, %project_week);
while ( my @row = &SQL_Fetch() ) {
  # skriv ut 7.0 som 7, 7.5 som 7.5

  for (4..10) {
  	$row[$_] = int $row[$_] if ( int($row[$_]) == $row[$_]);
  }
  @rader[$row[0]] = \@row;
  $antal_rader++;

  $project_week{$row[1]} = 1;
}


my($projects, $null_filter);

# plocka ut alla projekt rapportören är med i (som inte är avslutade) och är märkta visible
# fast om man kollar på en slutrapporterad vecka sker ingen filtrering
my $proj_filter;
if ($tyear*100+$tweek < $year*100+$week) {
	$SQL = qq|SELECT projid
						FROM projectmember
						WHERE userid = $user AND visible = 1|;
	&SQL_Execute($SQL);
	my @myprojects;
	while ( my $proj = &SQL_Fetch ) {
		push(@myprojects, $proj);
	}
	$projects = join(',', @myprojects);
	$null_filter = "And (closed Is NULL)";
}
else {
	$projects = join(',', keys %project_week);
}

# GDA 20130615
print qq|
<script type='text/javascript'>
	var returnAjax = null;
</script>|;
# GDA 20130615

print "<SCRIPT language='javascript'>\n";
print "var projectV = [\n\t[0, '', 0],\n";

my(%projekt_hash, @projnr, $antal_projekt);

if ($projects) {
	$SQL = qq|Select p.projid, c.name, p.description, activities_mask
	          From projects p, customers c
	          Where p.projid IN ($projects) And (p.custid = c.custid) $null_filter
	          Order By projid|;
	&SQL_Execute($SQL);

	while ( my($projnum, $projnamn, $beskrivning, $mask) = &SQL_Fetch() ) {
	  push(@projnr, $projnum);
	  print "\t[$projnum, '$projnum - $projnamn - $beskrivning', $mask],\n";
	  $antal_projekt++;
	}
}
print "\t[400, 'Absent', 0]\n];\n";

my($preScript, $latest_week);
if ( !$antal_rader ) {

	$SQL = qq|Select max(week)
          	From reports_$year
          	Where (week < $week) And (userid = $user)|;
	&SQL_Execute($SQL);

	if ( $latest_week = &SQL_Fetch ) {
		$preScript = "function preFill() {\n";
		$SQL = qq|Select row,projid,actid,timecode,monday,tuesday,wednesday,thursday,friday,saturday,sunday,theme
          		From reports_$year
          		Where (week = $latest_week) And (userid = $user)
          		Order By row|;
		&SQL_Execute($SQL);
		while ( my @row = &SQL_Fetch() ) {
			for (4..10) {
      				$row[$_] = int $row[$_] if ( int($row[$_]) == $row[$_]);
    			}
    			$row[11] =~ s/'/\'/g;
    			$row[11] = "'" . $row[11] . "'";
			$preScript .= "\tpreFillRow(" . join(", ", @row) . "," .$row_number . ");\n";
		}
		$preScript .= "}\n";
	}
}



print "var timecodesV = [\n";
my $t =0;
foreach (@tidkoder) {
	print "\t[" . $t++ . ", '$_']";
	if ($t <= $#tidkoder) {
		print ",\n";
	}
}
print  "\n];";
#print  "\t[" . ++$t . ", '']\n];";

print "\nvar absentV = [\n\t[0, ''],";
my $s = 0;
foreach (@absent_vals) {
	print "\t[" . (++$s + 500) . ", '$_']";
	if ($s <= $#absent_vals) {
		print ",\n";
	}
}
#print  "\t[" . (++$s + 500) . ", '']\n];";
print  "\n];";


my $lopnr = 1;
# plocka ut alla löpnummer (aktiviteter)
$SQL = qq|Select actid, name, maskid
          From activities
          Order By name|;
&SQL_Execute($SQL);

print   "\nvar tasksV = [\n\t[0, '', 0],\n";
while ( my($lopnum, $lopnamn, $mask) = &SQL_Fetch() ) {
	print "\t[$lopnum, '$lopnamn', $mask],\n";
}
print "\t[0, '', 0]\n]\n";

print "</SCRIPT>\n";


my $datumet = &Datum($week,$week,$year);

#print "<body bgcolor=\"#FFCC66\" onload=\"init_sums(document.table);\" leftmargin=\"30\" style=\"background: #FFCC66 url($RELPATH/photo.jpg) bottom right no-repeat fixed\">\n";
print "<body onload=\"init_sums(document.table, $row_number);\" leftmargin=\"30\" style=\"background: url($RELPATH/mapfond-droite.png) bottom right no-repeat fixed\">\n";

print "<p class=\"rubrik\">Timereporting $name [$user]</p>\n";
print "<form method=\"get\" action=\"$scriptname\" onsubmit=\"return f_change();\" name=\"vecka\">\n";

print "<div class=outerBorder style=\"width: 500px\">\n";
print "<div class=innerBorder>\n";

print "<table border=\"0\" width=\"500>\"  cellspacing=1 cellpadding=0=><tr><td>\n";
print "<span id=week>s $week</span> <span id=period>$datumet $year</span></td><td>\n";

print "<script language=\"JavaScript\"><!--\n";
print "chooseWeek($week, '');\n";
print "--></script>\n";

print "  <select name=\"year\">\n";
# skriv ut year-select med hänsyn taget till anställningsdatum
for ($firstyear..$lastyear+1) {
  if ($_ == $year) { print "    <option selected>$year</option>\n"; }
  else             { print "    <option>$_</option>\n"; }
}
print "  </select>\n";

print "  <input type=\"submit\" name=\"gotow\" value=\"Aller à la sem.\" >\n";
my $forra = $week -1;
my $nasta = $week +1;
$forra = 1 if ($forra < 1);
$nasta = 52 if ($nasta > 52);
print "  <input type=\"button\" name=\"but1\" value=\"<<\" onclick=\"GoTo($forra,$year);\" >\n";
print "  <input type=\"button\" name=\"but2\" value=\">>\" onclick=\"GoTo($nasta,$year);\" >\n";
print "</td></tr></table>\n";

print "</div></div>\n";

print "</form>\n";

# skriv ut kommentar till veckan
if ( $veckokommentar ) {
  $veckokommentar =~ s![\n]!<br>!ig; # substituera \n mot <br>
  print qq|<div id="info">$veckokommentar</div>|;
}

if ( !$firsttime && $latest_week ) {
	print "<a href=\"#\" class=\"a3\" onClick=\"preFill()\">Préremplir comme la semaine $latest_week</a>\n";
}


if ( $startweek &&  $week == $startweek ) {
  $msg .= " (starts week $startweek)";
}

#print "[$tyear, $tweek]";

my($finished, $disabled);
my $checka_week = $tweek + 1;
if ($year == $tyear) {

	$finished = 0;
	if ($week <= $tweek) {
		$finished = 1;
		# gammal vecka
		print "<form name=\"table\">\n";
		print "<p><b>$msg Cette semaine est marquée comme close. Prochaine semaine: <a class=\"a3\" href=\"$scriptname?gotow=1&week=$checka_week&year=$tyear\">$checka_week</a></b></p>\n";

	} elsif ($week == $checka_week) {
		# aktuell vecka
		print "\n<form method=\"post\" action=\"$scriptname?spara=1\" name=\"table\">\n";

	  if ($year == $lastyear && $week == $lastweek) {
			print "<p><b><font color=#666666>Cloturer</font></b><input name=finished type=\"checkbox\" disabled><i>Semaine actuelle!</i></p>\n";
		} else {
			print "<p><b>Cloturer</b><input type=\"checkbox\" name=\"finished\" value=\"1\"></p>\n";
		}

	} else {
		print "\n<form method=\"post\" action=\"$scriptname?saveweek=1\" name=\"table\" target=\"transmitter\">\n";
#           print "<p><b>Dernière semaine non close <a class=\"a3\" href=\"$scriptname?gotow=1&week=$checka_week&year=$tyear\">$checka_week</a></b></p>\n";
	}

} elsif ($year < $tyear) {
	$finished = 1;
	# gammalt år, ej sparmöjlighet
	print "<form method=\"post\" name=\"table\">\n";
	print "<p><b>$msg La semaine est close. Prochaine semaine: <a class=\"a3\" href=\"$scriptname?gotow=1&week=$checka_week&year=$tyear\">$checka_week</a></b></p>\n";
}
elsif ($year > $tyear) {
	$finished = 0;
	# nästa år
	print "<form method=\"post\" action=\"$scriptname?spara=1\" name=\"table\">\n";
	print "<p><b>Dernière semaine non close <a class=\"a3\" href=\"$scriptname?gotow=1&week=$tweek&year=$tyear\">$checka_week</a></b></p>\n";
}
if ($finished) {
	$disabled = 'disabled';
}
# Ajout BDE - Outils de conversion vers SAP
if (!$finished) {
  print "<input type=\"button\" name=\"saveweek\" value=\"Save\" onClick=\"f_checkbox(event)\">";
}
print "<a class=\"a3\" target=\"new\" href=\"http://lille/hrpic_tools/rodeo_to_hrpic.php?user_select=$user\" style=\"margin-left:20px\">Report mensuel</a> <b>pour saisie dans SAP</b>";
print "\t\t // \t<a class=\"a3\" target=\"new\" href=\"../hrpic_tools/";
if ($user == "78") {
	print "trace_evolutions";
} else {
	print "suivi_evolutions";
}
print ".php\" style=\"margin-left:20px\">Gestion des &eacute;volutions</a> <b>pour suivi et facturation</b>";


# skriv ut multiV som kopplar varje projekt till en unik aktivitetslista
print qq|
<script language="JavaScript">
	$preScript;
</script>

<table border=0 width=300><tr><td>
<div class=outerBorder>
<div class=innerBorder>

<table  border="0" cellspacing="0" cellpadding="0" >
  <tr class=tr_header>
    <td>&nbsp;&nbsp;Projet</td>
    <td>&nbsp;Activité</td>
    <td>&nbsp;Timecode</td>
    <td $red_day[1] align="center">Lun</td>
    <td $red_day[2] align="center">Mar</td>
    <td $red_day[3] align="center">Mer</td>
    <td $red_day[4] align="center">Jeu</td>
    <td $red_day[5] align="center">Ven</td>
    <td             align="center">Sam</td>
    <td             align="center">Dim</td>
    <td align="center">Tot</td>
    <td>&nbsp;Thème</td>
    <td>&nbsp;Commentaire</td>
  </tr>

<script language="JavaScript"><!--

|;

my $i;
for ($i=1; $i<=$row_number; $i++) {
  my $pnr = int($rader[$i][1]);
  my $lnr = int($rader[$i][2]);
  my $tnr = int($rader[$i][3]);

  my @celler;
  for (0..6) {
    $celler[$_] = $rader[$i][$_ + 4] + 0;
  }
  my $celler2 = join(', ',@celler);
  my $theme   = $rader[$i][11];
  my $com     = $rader[$i][12];

  $theme =~ s/\\/\\\\/g;
  $theme =~ s/'/´/g;
  $com =~ s/\\/\\\\/g;
  $com =~ s/'/´/g;

  print "ProjektOption($i,$pnr,$lnr,$tnr,'$disabled',$celler2,'$theme','$com',$row_number);\n";
}
print "--></script>\n";

print qq|
  <tr>
    <td colspan="13"><img src="dummy.gif" width="1" height="8"></td>
  </tr>
  <tr>
    <td colspan="3" align="right" class="td_r">Somme:</td>
    <td><input type="text" name="monday"    class="bsum" readonly></td>
    <td><input type="text" name="tuesday"   class="bsum" readonly></td>
    <td><input type="text" name="wednesday" class="bsum" readonly></td>
    <td><input type="text" name="thursday"  class="bsum" readonly></td>
    <td><input type="text" name="friday"    class="bsum" readonly></td>
    <td><input type="text" name="saturday"  class="bsum" readonly></td>
    <td><input type="text" name="sunday"    class="bsum" readonly></td>
    <td><input type="text" name="summa"     class="bsum" style="width:30px; color:red; font-size:13px; font-style:bold;" readonly></td>
    <td class="td_r" align="right" colspan="2">Heures restantes:<input type="text" name="tidbank" class="bsum" value="$tidbank" readonly>[$tidbank]</td>
  </tr>
 <tr>
  <td align="center" colspan="13">
    <table width="100%" border="0" cellspacing="1" cellpadding="0">
    <tr class=tr_header>
      <td id=statusbar class=statusbar>&nbsp;</td>
      <td width=100 class=insetBorder>&nbsp;Heures&nbsp;théoriques:&nbsp;$arbetstid&nbsp;</td>
      <td id=statusbar3  width=50>&nbsp;</td>
    </tr>
    </table>
  </td>
 </tr>
</table>
</div>
</div>
</td>
</tr>
</table>
  <input type="hidden" name="orig_tidbank" value="$tidbank">
  <input type="hidden" name="arbetstid" value="$arbetstid">
	<input type="hidden" name="week" value="$week">
	<input type="hidden" name="year" value="$year">
	<input type="hidden" name="transmitter" >
|;

if (!$finished) {
  print "<input type=\"button\" name=\"saveweek\" value=\"Save\"   onClick=\"f_checkbox(event)\">\n";
  print "<input type=\"button\" name=\"konsol\" value=\"Paramètres...\" onclick=\"return f_inst($week,$year);\" >\n";
  print "<input type=\"button\" name=\"yearview\" value=\"Vue annuelle\" onclick=\"popupWindow('$scriptname?visayear=$year',800,450)\" >\n";
  print "<input type=\"reset\" name=\"reset\" value=\"Reset\" onclick=\"return f_change();\" >\n";
}
print "<input type=\"button\" name=\"logout\" value=\"Déconnexion\" onclick=\"LogOut($user);\" >";


print "</form><iframe id=transmitter name=transmitter ></iframe>\n";

print "<div id=\"footer\">$FOOTER</div>";
print "</body>\n";
print "</html>\n";

}

##########################################################################################
sub PrintHeader($) {

$PRINTHEADER = 1;
my @inp = @_;

my $tidbank = $inp[0] + 0;
my $arbetstid = $inp[2] + 0;

$_ = <<EOP;
Content-type: text/html

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
  <title>Timereporting</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">

  <link REL="SHORTCUT ICON" HREF="$RELPATH/favicon.ico">
  <link REL="ICON" HREF="$RELPATH/favicon.ico">

<script language="javascript">
<!--
//if (top.frames.length != 0) top.location=document.location;
var scriptName  = '$scriptname';
-->
</script>

<LINK href="$RELPATH/rodeo2.css" rel="stylesheet" type="text/css">

EOP
print;

print "</head>\n"

}

##########################################################################################


sub UserConfig {

my $error_message;

my $week = $qform{'week'};
my $year = $qform{'year'};

if ( my $consave = $qform{'consave'} ) {

  my $projantal = $form{'projantal'};
  my @checked;
  for (1..$projantal) {
  	push(@checked, $form{'cb'.$_}) if ($form{'cb'.$_});
  }
  my $checks = join(',', @checked);

  # sätt första visible = 0 för alla deltagarens projekt
  $SQL = qq|Update projectmember
            Set visible = 0
            Where (userid = $user)|;
  &SQL_Execute($SQL);
  if ( $checks ) {        # om det finns projekt som ska vara visible

    $SQL = qq|Update projectmember
              Set visible = 1
              Where (userid = $user) And projid In ($checks)|;
    &SQL_Execute($SQL);
  }
}

if ( $qform{'changepass'} ) {
  my $oldpass2   = $form{'oldpass'};
  my $password  = $form{'password'};
  my $password2 = $form{'password2'};

  if ($password eq $password2) {
    my $passSalt = "saltet är gott";
    $SQL = qq|Select password
              From users
              Where userid = $user|;
    &SQL_Execute($SQL);
    my $oldpass = &SQL_Fetch;
    if ( $oldpass eq crypt($oldpass2, $passSalt) ) {
      $password = crypt($password, $passSalt);
      $SQL = qq|Update users
                Set password = '$password'
                Where userid = $user|;
      &SQL_Execute($SQL);
      $error_message = "<p>Mot de passe changé !</p>\n";
    }
    else {
      $error_message = "<p>Ancien mot de passe invalide !</p>\n";
    }
  }
  else {
    $error_message = "<p>Mots de passe différents !</p>\n";
  }
}

&NamnLista($user);

$_ = <<EOP;

<!--<body bgcolor="#FFCC66"  leftmargin="30">//-->
<body>

<p class=\"rubrik\">CBL Timereporting System - Paramètres</p>
<p class=\"rubrik\">$user - $EmpNames{$user}</p>
<p class=\"rubrik2\">Affecté au project</p>

<form method="post" action="$scriptname?config=1&consave=1&week=$week&year=$year">

<table width="550" border="0" cellspacing="1" cellpadding="0" class=table_border>
<tr class=tr_header>
  <td width="70">Projectid</td>
  <td>Customer</td>
  <td>Description</td>
  <td>Project manager</td>
  <td>Visible</td>
</tr>

EOP
print;

$SQL = qq|Select pm.projid, c.name, p.description, p.projectmanager, visible, u.name, u.surname
          From projects p, customers c, projectmember pm, users u
          Where (p.custid=c.custid) And (p.closed Is NULL) And (pm.projid = p.projid)
                And (pm.userid=$user) And (u.userid = p.projectmanager)
          Order By p.projid|;
&SQL_Execute($SQL);

my $i = 0;
while ( my @proj = &SQL_Fetch() ) {
  $i++;

  print "<tr>\n";
  print "  <td>&nbsp;$proj[0]</td>\n";
  print "  <td>&nbsp;$proj[1]</td>\n";
  print "  <td>&nbsp;$proj[2]</td>\n";
  print "  <td>&nbsp;$proj[5] $proj[6]</td>\n";
  my $checked = $proj[4] ? 'checked' : '';
  print "  <td>&nbsp;<input type=\"checkbox\" name=\"cb$i\" value=\"$proj[0]\" $checked></td>\n";
  print "</tr>\n";

}
print "</table>\n";
print "</td></tr></table>\n";
print "<br><input type=\"submit\" name=\"Submit\" value=\"OK\" >\n";
print "<input type=\"button\" name=\"back\" value=\"Retour au reporting\" onclick=\"Back($week,$year);\" >\n";
print "<input type=\"hidden\" name=\"projantal\" value=\"$i\">\n";
print "</form>\n";


print qq|
<form method="post" name="ChangePassword" action="$scriptname?config=1&changepass=1&week=$week&year=$year">
$error_message
<table border="0" cellspacing="3" cellpadding="0" class=table_form>
<tr>
	<td colspan=2>Change password</td>
</tr>
<tr>
	<td>Old password:</td>
	<td><input type="password" name="oldpass" size="10" maxlength="10" onblur="clearStatusbar()"></td>
</tr>
<tr>
	<td>New password:</td>
	<td><input type="password" name="password" size="10" maxlength="10" onblur="clearStatusbar()"></td>
</tr>
<tr>
	<td>Repeat new password:</td>
	<td><input type="password" name="password2" size="10" maxlength="10" onblur="clearStatusbar()"></td>
</tr>
<tr>
   <td colspan=2 align=right>
   		<input type="button" onclick="validatePassword()" value="OK" >
   </td>
</tr>
<tr>
   <td colspan="2"><div id=statusbar2>&nbsp;</div></td>
</tr>
</table>
</form>
|;

}

##################################################################################

sub PrintLogin {

&NamnLista;

#print "<body bgcolor=\"#FFFF99\"  marginwidth=0 marginheight=0 topmargin=0 leftmargin=0>\n";
print "<body marginwidth=0 marginheight=0 topmargin=0 leftmargin=0>\n";
print "<div id=login_message>$login_message</div>" if ($login_message);
print "<table  style=\"background: url($RELPATH/fond.gif)\" id=logintable border=\"0\" cellpadding=5 cellpadding=0 width=\"100%\"><tr>\n";
print "<td style=\"border: 1px solid #FFFFFF;\" width=\"500\">\n";
print qq|<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="500" height="400" id="index" align="middle">
                      <param name="movie" value="../temps/index.swf">
                      <param name="menu" value="false">
                      <param name="quality" value="high">
                      <param name="bgcolor" value="#333333">
                      <embed src="../temps/index.swf" menu="false" quality="high" bgcolor="#333333" width="500" height="400" name="index" align="middle" allowscriptaccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"> 
                    </object></td><td>|;
if ($WRONG) {
  print "<p class=\"rubrik\">Fel lösenord. Försök igen</p>\n";
}

#print "<p class=\"rubrik\">Use password 'test' to login</p>\n";

print "<form method=post name=loginUser action=\"$scriptname?login=user\">\n";
print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" class=table_login_green><tr><td>\n";
print "<tr><td colspan=\"3\" class=\"td_login\">Report time</td><tr><td>\n";
print "  Employee:<br><select name=\"user\">\n";
foreach ( @emps) {
	print "  <option value=\"$_\">$EmpNames{$_}</option>\n";
}
print "  </select></td><td>\n";
print "  Password:<br><input type=\"password\" name=\"passwd\" maxlength=\"10\" class=\"ari2\" size=\"10\"></td>\n";
print "  <td><br><input type=\"submit\" name=\"Submit\"  value=\"Log in\"></td>\n";
print "</tr>\n";
print "</table>\n";
print "</form><br>\n";

print "<form method=post name=loginAdmin action=\"$scriptname?login=admin\">\n";
print "<table width=\"250\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" class=table_login_red>\n";
print "<tr><td colspan=3 class=\"td_login\">Manage&nbsp;projects</td><tr><td>\n";
print "  Project&nbsp;manager:<br><select name=\"user\">\n";
foreach ( @ProjLeaders, 666 ) {
  print "  <option value=\"$_\">$EmpNames{$_}</option>\n";
}
print "  </select></td><td>\n";
print "  Password:<br><input type=\"password\" name=\"passwd\" class=\"ari2\" size=\"10\" maxlength=\"10\"></td>\n";
print "  <td><br><input type=\"submit\" name=\"Submit\"  value=\"Log in\"></td>\n";
print "</tr>\n";
print "</table>\n";
print "</form><br>\n";

print "</td></tr></table>\n";

if ($WRONG) {
	my $form = ($qform{'login'} eq 'user') ? 'loginUser' : 'loginAdmin';

	print qq|
	<script>
		with (document.$form) {
			var sel = 0;
			for (i=0; i<user.length; i++) {
				if (user.options[i].value == '$WRONG')
					sel = i;
			}
			user.selectedIndex = sel;
		}
	</script>
	|;
}

print "<div id=\"footer\">$FOOTER</div>";
print "</body></html>\n";;

}

########################################################################


sub RapporteraProjekt {

&NamnLista;


my($project, $month, $week1, $week2, $year, $mensuel);
if ($qform{'sparafaktura'}) {
  $week1   = $form{'week1'};
  $week2   = $form{'week2'};
  $year    = $form{'year'};
  $project = $form{'projnr'};
  my $belopp  = $form{'belopp'} + 0;
  $belopp =~ s/[ ]+//;   # ta bort mellanslag
  my $timmar  = $form{'timmar'} + 0;

  my ($d, $m, $y) = ($times[3], $times[4], $times[5]);
  if ($d < 10) { $d = '0'.$d; }
  if ($m < 10) { $m = '0'.$m; }
  my $datum_skapad = "$y-$m-$d";

  $month = $form{'month'} || 'NULL';

  my $paymentdue = $form{'paymentdue'};
  $paymentdue =~ s/^(....)(..)(..)/$1-$2-$3/;

  my $unik = "$project-$week1-$week2";
  $SQL = qq|Insert Into invoices_$year (projid, month, week1, week2, hours, created_by, date_created, ID, amount, payment_due)
            Values ($project, $month, $week1, $week2, $timmar, $admin, '$datum_skapad', '$unik', $belopp, '$paymentdue') |;
  &SQL_Execute($SQL);
}
else {
  $project = $qform{'projnr'};
  $week1   = $qform{'week1'} + 0;
  $week2   = $qform{'week2'} + 0;
  $year    = $qform{'year'} || $lastyear;
  $month = $qform{'month'};
  $mensuel = $qform{'mensuel'} || $MONTH_REPORTS;
#  if ($MONTH_REPORTS  && !$month && $week2) {	"Adaptation hebdo/mensuel
  if ($mensuel  && !$month && $week2) {
    $month = &Week2Month($week2,$year);
  }
#  if ($MONTH_REPORTS  && $month) {		"Adaptation hebdo/mensuel
  if ($mensuel  && $month) {
    ($week1, $week2) = &Month2Week($month,$year);
  }
}

$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår

$SQL = qq|Select p.projid, c.name, p.description, projectmanager, overtime1, overtime2
          From projects p, customers c
          Where p.custid = c.custid
          Order By p.projid|;
&SQL_Execute($SQL);
my @projs = ('');
my(%kund, @projs, %projledare, @overtid_faktor);
while ( my ($idnr, $kundnamn, $beskr, $projl, $overtid1, $overtid2) = &SQL_Fetch() ) {
  push (@projs,$idnr);
  $kund{$idnr} = "$kundnamn - $beskr";
  if ($idnr == $project) {
    @overtid_faktor[1,2] = ($overtid1, $overtid2);
    $projledare{$idnr} = $projl;
  }
}

$SQL = qq|Select actid, name, description
          From activities
          Order By name|;
&SQL_Execute($SQL);

while ( my($lopnr, $namn)  = &SQL_Fetch() ) {
  $lopnr_namn{ $lopnr } = $namn;
}

print "<form method=\"get\" name=\"period_change\" action=\"$scriptname\">\n";
print "<input type=\"hidden\" name=\"faktura\" value=\"1\">\n";
print "<input type=\"hidden\" name=\"mensuel\" value=\"".!$mensuel."\">\n";
#if ( $MONTH_REPORTS ) {		"Adaptation hebdo/mensuel
if ( $mensuel ) {
print "<input type=\"submit\" name=\"Submit\" value=\"Hebdomadaire\" >\n";
}else{
print "<input type=\"submit\" name=\"Submit\" value=\"Mensuel\" >\n";
}
print "</form>\n";

print "<form method=\"get\" name=\"admin_change\" action=\"$scriptname\">\n";
print "<input type=\"hidden\" name=\"faktura\" value=\"1\">\n";
print "<input type=\"hidden\" name=\"mensuel\" value=\"$mensuel\">\n";
print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" class=table_border_form>\n";
print "<tr><td>Report horaire pour facturation</td></tr>\n";
print "<tr><td>Projet:\n";
print "  <select name=\"projnr\">\n";
foreach (@projs) {
  if ($_ == $project) { print "    <option selected value=\"$project\">$project $kund{$project}</option>\n"; }
  else                { print "    <option value=\"$_\">$_ $kund{$_}</option>\n"; }
}
print "  </select>\n";

# valbart om fakturorna ska vara vecko eller månadsbaserade
#if ( $MONTH_REPORTS ) {		"Adaptation hebdo/mensuel
if ( $mensuel ) {
  print "  <b>Mois:\n";
  print "<input type=\"hidden\" name=\"year\" value=\"$lastyear\">\n";
  print "<select name=\"month\">\n";
  for (0..12) {
    if ($_ == $month) { print "  <option selected value=\"$month\">$Months_W[$month]</option>\n"; }
    else              { print "  <option value=\"$_\">$Months_W[$_]</option>\n"; }
  }
  print "</select>\n";
  print "Année: <select name=\"year\">\n";
	my $sel_year = $year || $lastyear;
	my $sel;
	for ($FIRST_YEAR..$lastyear+1){
		$sel = ($_ == $sel_year) ? 'selected' : '';
		print "<option $sel value=\"$_\">$_</option>\n";
	}
	print "  </select>\n";
}
else {
  &PeriodSelect($week1,$week2, $year);
}

print "  <input type=\"submit\" name=\"Submit\" value=\"OK\" >\n";
print "</td></tr></table>\n";

print "</form>\n";

if (!$project) {
  &ProjektStatus;
  goto SLUT;
}

my $search_col;
# plocka ut den senaste raden i invoices_yyyy för projektet
#if ( $MONTH_REPORTS ) {		"Adaptation hebdo/mensuel
if ( $mensuel )     { $search_col = 'month'; }
else                { $search_col = 'week1'; }

$SQL = qq|Select Max($search_col)
          From invoices_$year
          Where projid = $project|;
&SQL_Execute($SQL);

my ($fakweek1, $fakweek2, $fakmonth, $timmar, $signatur, $belopp, $datum_skapad);
if ( my $fak_col = &SQL_Fetch() ) {
  $SQL = qq|Select week1, week2, month, hours, created_by, amount, date_created
            From invoices_$year
            Where (projid = $project) And ($search_col = $fak_col)|;
  &SQL_Execute($SQL);
  ($fakweek1, $fakweek2, $fakmonth, $timmar, $signatur, $belopp, $datum_skapad) = &SQL_Fetch();
  #print "($fakweek1, $fakweek2, $fakmonth, $timmar, $signatur, $belopp, $datum_skapad)";
}

print "<h2>Project $project - $kund{$project}\n";
print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('RapporteraProjekt')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";

my $led = $EmpNames{ $projledare{$project} };
print "<p>Responsable du projet: $led<br>\n";
print "Overtime factors ÖT1, ÖT2 ($overtid_faktor[1], $overtid_faktor[2])\n";
print "<a class=\"a3\" href=\"$scriptname?projektdeltagare=$project\">Participants</a></p>\n";

if (!$month) {
	#if ( $MONTH_REPORTS ) {		"Adaptation hebdo/mensuel
	if ( $mensuel ) {
		for (1..12) {
			&ProjektPeriod($project, $year, $_, 0, 0, $fakweek2, @overtid_faktor);
			print "<br><br>";
		}
		goto SLUT;

	} else {
		&ProjektPeriod($project, $year, 0, , $week1, $week2, $fakweek2, @overtid_faktor);
	}
}
else  {
	&ProjektPeriod($project, $year, $month, , $week1, $week2, $fakweek2, @overtid_faktor);
}


# Fakturera timmar om inte perioden stöter sig med en redan fakturerad period
my $behorig = $projledare{$project} == $admin || $AccessRight == 2;
my($ofakturerad_period, $redan_fakturerad, $period, $senaste_period);
#if ( $MONTH_REPORTS ) {		"Adaptation hebdo/mensuel
if ( $mensuel ) {
  $ofakturerad_period = $fakmonth < $month;
  $redan_fakturerad   = $fakmonth > $month;
  $period = $Months_W[$month];
  $senaste_period = "<font color=\"red\">$Months_W[$fakmonth]</font>";
}
else {
  $ofakturerad_period = $week1 > $fakweek2;
  $redan_fakturerad   = $week1 < $fakweek2;
  $period = "week $week1 - $week2";
  $senaste_period = "week  <font color=\"red\">$fakweek1 - $fakweek2</font>";
}

if ( $ofakturerad_period  && $behorig ) {
  print qq|
  <form method="post" name="invoice" action="$scriptname?faktura=1&sparafaktura=1">
  	<input type="hidden" name="projnr" value="$project">
  	<input type="hidden" name="week1" value="$week1">
  	<input type="hidden" name="week2" value="$week2">
  	<input type="hidden" name="month" value="$month">
  	<input type="hidden" name="year" value="$year">
  <p>Invoice period</p>
  <table cellspacing=3 borderwidth=0 class=table_form>
  	<tr>
  		<td>Paiement dû</td>
  		<td><input type="text" name="paymentdue" size="10" maxlength="10" class="ari2" onblur="clear_statusbar(this.form)"></td>
  	</tr>
  	<tr>
  		<td>Montant:</td>
  		<td><input type="text" name="belopp" size="7" maxlength="7" class="inputNumber" onblur="clear_statusbar(this.form)"></td>
  	</tr>
  	<tr>
  		<td>Heures:</td><td><input type="text" name="timmar" size="7" maxlength="7" class="inputNumber" onblur="clear_statusbar(this.form)"></td>
  	</tr>
  	<tr>
  		</td>&nbsp;<td>
  		<td align=right><input type="submit" name="submit"  value="Invoice" onclick="submitData(event, validera_invoice())"></td>
  	</tr>
  	<tr>
  		<td colspan="2"><div id=statusbar2>&nbsp;</div></td>
		</tr>
  </table>

  </form>
  |;
}
elsif ( $redan_fakturerad ) {
  print "<p>La période choisie $period inclut des semaines déjà facturées.<br>\n";
  print "La dernière période factutée est $senaste_period créée le $datum_skapad, où $belopp \€ [$timmar h] a été facturé par by $EmpNames{$signatur}.</p>\n";
}
else {
  if ( !$behorig ) {
    print "<p>Vous n'êtes pas autorisé à facturer ce projet. Seul le Responsable de Projet le peut.</p>\n";
  }
  if ($belopp) {
    print "<p>La dernière période factutée est  $senaste_period créée le $datum_skapad, où $belopp \€ [$timmar h] a été facturé par $EmpNames{$signatur}.</p>\n";
  }
}

SLUT:
}

#############################################################################
sub ProjektPeriod($$$@) {

my ($project, $year, $month, $week1, $week2, $fakweek2, @overtid_faktor) = @_;

#print "($project, $year, $month, $fakweek2, @overtid_faktor, $week1, $week2)";

my($day1, $day2) = (1, 7);

$year ||= $qform{'year'};

if ($month) {
	($week1, $week2, $day1, $day2) = &Month2Week($month,$year);
}

my $fakturerings_period;
my $valid_month;
if ( $month ) {
  $fakturerings_period = $Months_W[$month];
}
else {
  $fakturerings_period = &Datum($week1,$week2,$year);
  ($month, $valid_month) = &Datum2month($week1,$week2,$year);
}

print "<h3><i>Date: <font color=\"red\">$fakturerings_period, $year (week $week1 $Days[$day1] - $week2 $Days[$day2])</font></i></h3>\n";
if ( !$valid_month ) {
  print "<br><h3><font color=\"red\">Attention, la période couvre plusieurs mois, le dernier tient lieu de référence pour les taux.</font></h3>\n";
}

#print "[$month][$project]($week1 And $week2)($day1, $day2)<br>";

# plocka ut arvodet för gällande period
my %arvode;
$SQL = qq|Select userid, fee
          From fees_$year
          Where projid = $project And ($month Between month1 And month2)|;

&SQL_Execute($SQL);

my(%arvode, $user, $arv);
while ( ($user, $arv) = &SQL_Fetch ) {
	$arvode{$user} = $arv;
}

# Fixa en lista med alla som har minst EN kontrollerad tidrapport med uppgifter
# om aktuella projektet för veckointervallet.

my @member;
$SQL = qq|Select Distinct userid
          From reports_$year
          Where (projid = $project) And (week Between $week1 And $week2)|;
&SQL_Execute($SQL);
my($idnr, @member);
while ( $idnr  = &SQL_Fetch() ) {
  push (@member, $idnr);
}
my $userlista = join(', ',@member);

#goto SLUT if ( !$userlista );
return 0 if ( !$userlista );

print "<table border=\"0\" width=\"900\" cellspacing=\"1\" cellpadding=\"2\" class=table_border>\n";
print "<tr class=tr_header>\n";
print "  <td width=\"100px\">Membre</td>\n";
print "  <td>Heures hebdo</td>\n";
print "  <td>Total/période</td>\n";
print "</tr>\n";

my %kontroll;
my %slut_rapporterad;

# Fixa lista där för varje vecka i intervallet står om veckans tidrapport är kontrollerad
# left outer för att få även de rader där empid != kontroll (de som ej kontrollerats)
$SQL = qq|Select w.userid, week, checked_by, nick
          From (weeks w)
          LEFT OUTER JOIN users u ON u.userid = checked_by
          Where w.userid In ($userlista) And (week Between $week1 And $week2) AND (year = $year)
          Order By week|;
&SQL_Execute($SQL);
my(%kontroll, %slut_rapporterad);
while ( my($userid, $week, $sign, $nick) = &SQL_Fetch() ) {
  $kontroll{ $userid.'_'.$week } = $nick;
  $slut_rapporterad{ $userid.'_'.$week } = 1;
}

# BDE : ajouter commentaire dans les reports
#$SQL = qq|Select userid, week,actid,timecode,theme, monday,tuesday,wednesday,thursday,friday,saturday,sunday
$SQL = qq|Select userid, week,actid,timecode,theme, commentaire, monday,tuesday,wednesday,thursday,friday,saturday,sunday

          From reports_$year
          Where (projid = $project) And userid In ($userlista) And (week Between $week1 And $week2)
          Order By userid,week,row|;
&SQL_Execute($SQL);

my ($forbiraden, $olduser, $total, $grandtotal, $grandtotal_kr, $tog_hand_om_sista,
    $arvo, %sum_lopnr, %distinct_lopnr, @lopnr_lista,
    $overtid_sum_total, $odeb_sum_total, $lastrow, $color, $tidnr, $total_summa, $user_query);

# BDE : ajouter commentaire dans les reports
#while ( my($user, $week, $lopnr, $tidnr, $theme, @days) = &SQL_Fetch() ) {
while ( my($user, $week, $lopnr, $tidnr, $theme, $commentaire, @days) = &SQL_Fetch() ) {

  # förbered för övergång till månadsrapporter
  my $d1 = 1;
  my $d2 = 7;
# Correction du bug du jour décalé sur report mensuel
#  if    ($week == $week1) { $d1 = $day1 - 1; }
#  elsif ($week == $week2) { $d2 = $day2 - 1; }
  if    ($week == $week1) { $d1 = $day1; }
  elsif ($week == $week2) { $d2 = $day2; }
# fin de correction
  my $sum = 0;
#  print "[$week, $week1, $week2, $day1, $day2 : $d1, $d2]";
  foreach ($d1..$d2) {
    $sum += $days[$_ - 1] ;
    #print "d:".$_." days:".$days[$_ - 1];
  }
  $tidnr += 0;

  if ($user != $olduser) {
    if ( $forbiraden++ ) { # hoppa över första
TA_HAND_OM_SISTA:
##
# Affichage de la colonne "Total/période"
##
      $total += $overtid_sum_total - $odeb_sum_total;
      $grandtotal += $total;
      print "    </table></td>\n";
      print "<td align=\"center\">\n";
      $arvo = $arvode{ $olduser } + 0;
      $grandtotal_kr += $total*$arvo;

      my $odeb_och_overtid =  ($overtid_sum_total) ? "<br>dont $overtid_sum_total sont OT " : '';
      $odeb_och_overtid .= ($odeb_sum_total)    ? "<br>$odeb_sum_total heures FREE " : '';

      print "<p style=\"text-align:left\"><font color=\"red\">$total heures à $arvo \€ $odeb_och_overtid</font><br>";
      foreach (@lopnr_lista) {  # skriv, för alla aktiviteter den anställde
        my $lopnr = $_;            # utfört under perioden, antalet timmar
        my $lnr_sum = $sum_lopnr{$lopnr};
        $sum_lopnr{$lopnr} = 0;
        $distinct_lopnr{$lopnr} = 0;
        my $lnamn = $lopnr_namn{$lopnr};
##
# Section activité
##
        if ($lnr_sum) {print "<br><b>$lnamn ($lnr_sum)</b>"; }
##
# Détail de l'activité thème par thème
##
        my $local_SQL = qq|Select actid, week, theme, monday, tuesday, wednesday, thursday, friday, saturday, sunday
          From reports_$year
          Where (projid = $project) And (userid = $olduser ) And (week Between $week1 And $week2) And (actid = $lopnr)
          Order By actid,theme|;
        my $local_sth = $dbh->prepare($local_SQL);
        $local_sth->execute;
        my $theme_query_old;
        my $actid_old;
        my $sum_theme = 0;
        while ( my($actid, $week, $theme_query, @days_theme) = $local_sth->fetchrow_array ) {
          my $d1 = 1;
          my $d2 = 7;
          if ( $theme_query_old."_".$actid_old ne $theme_query."_".$actid ) {
            if ($sum_theme) {print "<br>&nbsp;- $theme_query_old ($sum_theme)"; }
            $theme_query_old = $theme_query;
            $actid_old = $actid;	
            $sum_theme = 0;
          }
# Correction du bug de décalage d'une journée
#          if    ($week == $week1) { $d1 = $day1 - 1; }
#          elsif ($week == $week2) { $d2 = $day2 - 1; }
          if    ($week == $week1) { $d1 = $day1; }
          elsif ($week == $week2) { $d2 = $day2; }
# fin de correction
          my $sum = 0;
#         print "[$week, $week1, $week2, $day1, $day2 : $d1, $d2]";
          foreach ($d1..$d2) {
            $sum_theme += $days_theme[$_ - 1] ;
          }
        }
        if ($sum_theme) {print "<br>&nbsp;- $theme_query_old ($sum_theme)"; }
      }
      print "</p>";
      @lopnr_lista = ();
      print "</td></tr>\n";

      $total = 0;
      $overtid_sum_total = 0;
      $odeb_sum_total = 0;
      $lastrow = -5;

      goto HOPPA_TILLBAKA if ($tog_hand_om_sista);
    }

    print "<tr>\n";
    print "  <td>$EmpNames{$user}</td>\n";
    print "  <td>\n";
    print "    <table border=\"0\" border=\"0\" cellpadding=\"0\" cellspacing=\"3\" >\n";
    print "    <tr>\n";
    print "      <td width=\"30\"><b>Sem</td>\n";
    print "      <td width=\"40\"><b>Heure</td>\n";
    print "      <td width=\"50\"><b>Timecode</td>\n";

# BDE : afficher les commentaires dans les reports
#    print "      <td ><b>Activité</td>\n";
#    print "      <td width=\"30\"><b>Vu</td>\n";
#    print "      <td><b>Thème</td>\n";
    print "      <td width=\"90px\"><b>Activité</td>\n";
    print "      <td width=\"90px\"><b>Thème</b></td>\n";
    print "      <td ><b>Commentaire</b></td>\n";

    print "    </tr>\n";
    $olduser = $user;
  }
  $total += $sum;

  $sum_lopnr{ $lopnr } += $sum;
  if ( !$distinct_lopnr{ $lopnr } ) {
    push(@lopnr_lista,$lopnr);
    $distinct_lopnr{ $lopnr } = 1;
  }
  $color = 'black';
  my $kollad = '';
  my $veck;
  if ($week ne $lastrow) {
    $veck = $week;

    if ($veck < $fakweek2) {
      $color = 'red';              # röd när veckan blivit fakturerad
    }
    $kollad = $kontroll{ $user.'_'.$veck };
    $lastrow = $week;
  }
  else {
    $veck = '&nbsp;';
  }

  my $overtid_sum_total;
  if ($tidnr == 1 || $tidnr == 2) {                # (Normal, ÖT1, ÖT2, Odeb, Odeb ÖT, Odeb ÖT2)
    $overtid_sum_total += $sum*($overtid_faktor[$tidnr] - 1);
    $sum = ( $sum*$overtid_faktor[$tidnr] )."($sum)"; # skriv originaltimmarna inom parentes
  }

  if ($tidnr > 2) {                # odebiterat
    $odeb_sum_total += $sum;
    $sum = "<font color=\"green\"><div style=\"text-decoration: line-through\">$sum</div></font>";
  }

  print "    <tr>\n";
  if ( $slut_rapporterad{ $user.'_'.$week } || $veck eq '&nbsp;') {  # om inte veckan är märkt som slutrapporterad omges den av parenteser
    print "      <td>&nbsp;<font color=\"$color\">$veck</font></td>\n"; }
  else {
    print "      <td>&nbsp;<font color=\"$color\">($veck)</td>\n"; }
  print "      <td>$sum</td>\n";
  print "      <td>$tidkoder[$tidnr]</td>\n";
  print "      <td>$lopnr_namn{$lopnr}</td>\n";

# BDE afficher les commentaires dans les reports
#  print "      <td>$kollad</td>\n";
  print "      <td>&nbsp;<i>$theme</i></td>\n";
  print "      <td>$commentaire</td>\n";

  print "    </tr>\n";
}


if ( $forbiraden ) {  # ta hand om sista fetchen
  $tog_hand_om_sista = 1;
  $user_query = $user;
  goto TA_HAND_OM_SISTA;
}
HOPPA_TILLBAKA:

$total_summa = &Format($grandtotal_kr);

print "<tr><td class=\"rubrik2\" colspan=\"3\" align=\"center\" height=\"30\">";
print "Nombre total d'heures dans le projet $project pour $fakturerings_period: <font size=3 color=\"red\">".($grandtotal / $hours_a_day)."j ($total_summa \€)</td></tr>\n";
print "</table>\n";


}
#############################################################################

sub PrintEmployees {

my $order = $qform{'order'} || 'userid';

my $behorighet = ($AccessRight == 2);
my $sparaemp = $qform{'sparaemp'};

my @empch;

if ( $behorighet &&  $sparaemp) {
  my $name       = $form{'name'} || ' ';
  my $surname    = $form{'surname'} || ' ';
  my $nick       = $form{'nick'} || ' ';
  my $title      = $form{'title'} + 0;
  my $password   = $form{'password'};
  my $startdatum = $form{'startdatum'};
  my $tidbank    = $form{'tidbank'};
  my $email      = $form{'email'};
  my $slutat = '';
  my $passy = '';

  if ( $form{'slutat'} ) {
    my $day =  $times[3];
    my $month = $times[4];
    my $year =  $times[5];

    if ($day < 10)   { $day = '0'.$day; }
    if ($month < 10) { $month = '0'.$month; }
    $slutat = ",slutat = '$year$month$day'";
  }

  if ($password) {
    $password = crypt($password, "saltet är gott");
    $passy = qq|,password = '$password'|;
  }

  my($cur_year, $change);
  if ($change = $form{'change'}) {

    if ( $startdatum ) {
    	$cur_year = substr($startdatum,0,4);
      $startdatum = ", begindate = '$startdatum', current_year = $cur_year";
      $tidbank += 0;
      $tidbank = ", timebank = $tidbank";
    }

    $SQL = qq|Update users
              Set name = '$name', surname = '$surname', nick = '$nick', authority = $title, email = '$email'
                          $passy $slutat $startdatum $tidbank
              Where (userid = $change)|;
    &SQL_Execute($SQL);
  }
  else {
    my $empid = $form{'empid'} || goto HOPPA;

    $cur_year = substr($startdatum,0,4);
    $SQL = qq|Insert Into users (userid, name, surname, nick, authority, password, begindate, timebank, current_year, email)
              Values ($empid, '$name', '$surname', '$nick', $title, '$password', '$startdatum', $tidbank, $cur_year, '$email')|;
    goto HOPPA if ( ! &SQL_Execute($SQL) );  # typiskt fel är att empid är existerande
  }
}

my $change = $qform{'change'};
HOPPA:

my $filter;
if  ( !$qform{'slutat'} ) {
  $filter = "Where resigned Is NULL";
}
else {
  $filter = "Where Not (resigned Is NULL)";
}

#my %titles = (0=>'Salarié', 1=>'Responsable de projet', 2=>'Responsable de facturation' );

print qq|<h2>Users
         <a href="#" title="Help" onClick="helpWin('EmployeeSida')"><img src="$RELPATH/help.gif" width="35" height="16" border="0" alt="Help"></a></h2>
         <table border="0 "width="400"><tr><td class="td_spalt">Cliquer <i>userid</i> pour modifier le salarié.
         Cliquer <i>assigned to</i> pour affecter le user aux projets.
         </td></tr></table><br>|;

if (!$change) {
  if ( $behorighet ) {
    print "<input type=\"button\" name=\"Input\" value=\"New user\"  onClick=\"javascript:showLayer('layer1')\">\n";
    print "<input type=\"button\" name=\"slutat\" value=\"Show resigned users\"   onClick=\"javascript:document.location='$scriptname?showemps=1&slutat=1'\"><br><br>\n";
  }

  # hämta ut jur många projekt varje anställd är med i
  $SQL = qq|Select userid, Count(*)
            From projectmember
            Group By userid|;
  &SQL_Execute($SQL);
  my %pCount;
  while ( my ($idnr, $count) = &SQL_Fetch() ) {
    $pCount{$idnr} = $count;
  }

  # MySql specifik lösning (mysql har inte views)
  if ( $MYSQL ) {
    $SQL = qq|CREATE TEMPORARY TABLE maxweek_view
              Select userid, Max(year*100+week) As maxweek
              From weeks
              Group By userid|;
    &SQL_Execute($SQL);
    # hämta varje anställdes senaste tidbank från weeks_yyyy
    $SQL = qq|Select w.userid, w.timebank, t.week
              From (maxweek_view m, weeks w) LEFT OUTER JOIN timebank t ON ( (t.year*100+t.week) = m.maxweek AND t.userid = w.userid)
              Where (m.userid = w.userid) And m.maxweek = (w.year*100+w.week)|;
  }
  else {
    # hämta med subselect subselect (ej MySql)
    $SQL = qq|Select w.userid, w.timebank
              From weeks w
              Where (w.year*100+w.week) = ( Select Max(w2.year*100+w2.week)
                                            From weeks w2
                                            Where w2.userid = w.userid)|;
  }

  &SQL_Execute($SQL);
  my %tidbank;
  while ( my ($idnr, $tid, $tweek) = &SQL_Fetch() ) {
    $tidbank{ $idnr } = $tweek ? "<font color=red>$tid</font>" : $tid;
  }

  # radera temporära tabellen
  if ( $MYSQL ) {
    $SQL = qq|Drop Table maxweek_view|;
    &SQL_Execute($SQL);
  }

  print "<table  class=table_border border=\"0\" cellspacing=\"1\" cellpadding=\"1\" >\n";
  print "<tr class=tr_header>\n";
  print "  <td>userid</td>\n";
  print "  <td><a href=\"$scriptname?showemps=1&order=name\">Prénom</a></td>\n";
  print "  <td><a href=\"$scriptname?showemps=1&order=surname\">Nom</a></td>\n";
  print "  <td>Nick</td>\n";
  print "  <td><a href=\"$scriptname?showemps=1&order=authority\">Autorisation</a></td>\n";
  print "  <td>Affecté à</td>\n";
  print "  <td>Timebank</td>\n";
  print "</tr>\n";

  $SQL = qq|Select userid, name, surname, nick, authority, timebank
            From users
            $filter
            Order By $order|;
  &SQL_Execute($SQL);

  while ( my ($idnr, $name, $surname, $nick, $title, $begin_tidbank)  = &SQL_Fetch() ) {
    print "<tr>\n";
    my $linknr = $behorighet ? "<a href=\"$scriptname?showemps=1&change=$idnr\">$idnr</a>" : $idnr;
    print "  <td>$linknr</td>\n";
    print "  <td>$name</td>\n";
    print "  <td>$surname</td>\n";
    print "  <td>$nick</td>\n";
    print "  <td>$titles{$title}</td>\n";
    my $count = $pCount{$idnr} + 0;
    print "  <td align=\"center\"><a href=\"$scriptname?projectmembers=1&user=$idnr\">$count</a></td>\n";
    # om inte vecka rapporterats visas begin_tidbank

    my $timebank = $tidbank{$idnr};
    if ( $timebank.'-' eq '-' ) {  # speciallösn för att skilja på null och 0
      $timebank = $begin_tidbank;
    }

    print "  <td align=\"center\"><a href=\"$scriptname?showtidbank=$idnr\">$timebank</a></td>\n";
    print "</tr>\n";
  }
  print "</table>\n";

}


&PrintNyEmployee($change, @empch) if ( $behorighet );

}

#############################################################################
sub PrintNyEmployee($@) {

my ($change, @empch) = @_;

my ($startdatum, $tidbank, $disabledate, $slutat, $visible, $form_title, $maxweek);

if ($change) {
	$visible = "normal";
	$form_title = "Change userdetails";

  $SQL = qq|Select userid, name, surname, nick, authority, begindate, timebank, resigned, email
            From users
            Where userid = $change|;
  &SQL_Execute($SQL);
  @empch = &SQL_Fetch();

  # LIMIT 1
  my ($mssql_top, $mysql_limit);
  if ($MYSQL) {
  	$mysql_limit = 'LIMIT 1';
  } else {
  	$mssql_top = 'TOP 1';
  }
  $SQL = qq|Select $mssql_top week
            From reports_$lastyear
            Where userid = $change
            $mysql_limit|;
  &SQL_Execute($SQL);
  if ( !($maxweek = &SQL_Fetch) ) { # inga veckor rapporterade ännu - ok att ändra startdatum och tidbank
    $startdatum = $empch[5];
    $tidbank = $empch[6];
  }
  else {
    $disabledate = 'disabled'; #   startdatum tillåts int eändras
  }
  if ( $empch[7] ) {
    $slutat = 'checked';
  }
}
else {
	$visible = "hidden";
	$form_title = "New user";
}

####################################################

my %sel = ($empch[4] => 'selected');
my($empno_input, $layer);
if (!$change) {
	$layer = "layer1";
  $empno_input =  "<tr><td class=\"td_form\" colspan=\"3\">Userid: <input type=\"text\" name=\"empid\" size=\"4\" maxlength=\"4\" class=\"ari2\" onblur=\"clear_statusbar(this.form)\"></td></tr>\n";
}
else {
  $empno_input = "<tr><td class=\"td_form\">Userid: <input type=\"text\" name=\"empid\" size=\"4\" maxlength=\"4\" class=\"ari2disabled\" value=\"$change\" disabled></td>\n" .
                 "<td></td><td class=\"td_form\">Resigned: <input type=\"checkbox\" name=\"slutat\" value=\"1\" $slutat></td></tr><tr>\n";
  $layer = "layer2";
}

if(!$empch[5]) {
  $empch[5] = "yyyymmdd";
}

print qq|

<div id="$layer">

	<form method="post" name="add_user" action="$scriptname?showemps=1&sparaemp=1">
	<table border="0" cellspacing="1" cellpadding="2" class=table_form>
	<tr>
	<td>
	<table border="0" cellspacing="0" cellpadding="0" class=title_bar width="100%">
	  <tr><td>&nbsp;<font color="white">$form_title</font></td>
	      <td align="right" valign="middle"><a href=# onclick="helpWin('Employee')"><img src="$RELPATH/question.gif"width="16" height="16" border="0" alt="Help"></a><a href="#" onClick="javascript:hideLayer('$layer')"><img src="$RELPATH/kryss.gif" width="16" height="16" border="0" alt="St&auml;ng ned formul&auml;r"></a></td></tr>
	</table>
	<table border="0" cellspacing="5" cellpadding="0"><tr>
	  $empno_input
	<td >Prénom:<br><input type="text" name="name" size="15" value="$empch[1]" maxlength="30" class="ari2" onblur="clear_statusbar(this.form)"></td>
	<td >Nom:<br><input type="text" name="surname" size="15" value="$empch[2]"  maxlength="30" class="ari2" onblur="clear_statusbar(this.form)"></td>
	<td >Nick:<br><input type="text" name="nick" size="4" value="$empch[3]"  maxlength="4" class="ari2" onblur="clear_statusbar(this.form)"></td></tr><tr>
	<td colspan="2" >Autorisation:<br><select name="title">
	    <option $sel{0} value="0">Salarié</option>
	    <option $sel{1} value="1">Responsable de projet</option>
	    <option $sel{2} value="2">Responsable de facturation</option>
	 </select></td>
	<td >Mot de passe:<br><input type="password" name="password" size="10" maxlength="10" class="ari2"></td>
	</tr><tr>
	<td >Date de début:<br><input type="text" name="startdatum" $disabledate size="8" maxlength="8" value="$empch[5]" $disabledate maxlength="2" class="ari2$disabledate" onblur="clear_statusbar(this.form)"></td>
	<td >Timebank:<br><input type="text" name="tidbank$disabledate" $disabledate size="3" value="$tidbank" maxlength="3" class="ari2$disabledate" onblur="clear_statusbar(this.form)"></td></tr><tr>

	 <td colspan="3">E-mail:<br><input type="text" class="ari2" name="email" size="40" value="$empch[8]"></td></tr><tr>

	<td colspan="3" align="right"><input type="hidden" name="change" value="$change">
	<input type="submit" name="submit" value="OK"  onClick="submitData(event, validera_users())"></td></tr><tr>

	 <td colspan="3"><div id=statusbar2>&nbsp;</div></td>
	</tr>
	</table>
	</td></tr></table>
</form>
</div>
|;

####################################################

}

#############################################################################
sub PrintProjects {

my $order = $qform{'order'} || 'projid';

&NamnLista;

my $ledare;
my $project_type;

my $avslutade = $qform{'avslutade'};
my $sparaproj = $qform{'sparaproj'};
if ( $sparaproj ) {
  my $projnr = $form{'projnr'};
  my $kundnr = $form{'kundnr'};
  $ledare = $form{'ledare'} || ' ';
  $project_type = $form{'project_type'};
  my $beskr  = $form{'beskrivning'} || ' ';

  my $overtid1 = $form{'overtid1'} || 1;
  my $overtid2 = $form{'overtid2'} || 1;

  my $mask = $form{'mask'} + 0;

  if (my $change = $form{'change'}) {
  	my $avslut = $form{'avslut'} ? qq|'$lastyear$times[4]$times[3]'| : 'NULL';

    $SQL = qq|Update projects
              Set custid = $kundnr, projectmanager = $ledare, description = '$beskr', project_type_id = $project_type,
              activities_mask = $mask, closed = $avslut, overtime1 = $overtid1, overtime2 = $overtid2
              Where projid = $change|;
	
	print $SQL;
    &SQL_Execute($SQL);
  }
  else {
    goto SKIPSPARA if (!$projnr);
    $SQL = qq|Insert Into projects (projid, custid, projectmanager, description, activities_mask, overtime1, overtime2, project_type_id)
              Values ($projnr, $kundnr, $ledare, '$beskr', $mask, $overtid1, $overtid2, $project_type)|;
    &SQL_Execute($SQL);

# projektledaren bör adderas till projektet direkt
    $SQL = qq|Insert Into projectmember (userid, projid, visible)
              Values ($ledare, $projnr, 1)|;
    &SQL_Execute($SQL);
  }
}
SKIPSPARA:

my $change = $qform{'change'};

#####fixa lista med kunder
$SQL = qq|Select custid, name
          From customers
          Order By custid|;
&SQL_Execute($SQL);
my @kunder = (0);
my %kundnamn;
while ( my ($kundnr, $kund)  = &SQL_Fetch() ) {
  push @kunder, $kundnr;
  $kundnamn{$kundnr} = $kund;
}

my $filter;
if ($avslutade) {
  $filter = "And Not (closed Is NULL)";
}
else {
  $filter = "And (closed Is NULL)";
}

$SQL = qq|Select p.projid, Count(u.userid)
          From projectmember p , users u
          Where (p.userid = u.userid) And (u.resigned Is NULL)
          Group By p.projid|;
&SQL_Execute($SQL);
my %pCount;
while ( my ($proj, $count) = &SQL_Fetch() ) {
  $pCount{$proj} = $count;
}


print qq|<h2>Projets
         <a href="#" title="Help" onClick="helpWin('ProjektSida')"><img src="$RELPATH/help.gif" width="35" height="16" border="0" alt="Help"></a></h2>
         <table border="0 "width="400"><tr><td class="td_spalt">
         Ajout/Modif projets. Cliquer la colonne <i>Membre</i> pour affecter des mecs aux projets</td></tr></table><br>|;

print "<input type=\"button\" name=\"Input\" value=\"Nouveau projet\"  onClick=\"javascript:showLayer('layer1')\">\n";

if (!$avslutade) {
  print "<input type=\"button\" name=\"Input\" value=\"Montrer les projets clos\"   onClick=\"javascript:document.location='$scriptname?projects=1&avslutade=1'\"><br><br>\n";
}
else {
  print "<br><p><b>Projets clos</b></p>\n";
}

my($avslut, $titel_avslut, $layer, $form_title, @projch);
$SQL = qq|Select id, libelle
          From project_type|;
&SQL_Execute($SQL);
my %projTypes;
while ( my ($projTypeId, $projTypeLibelle)  = &SQL_Fetch() ) {
  $projTypes{$projTypeId} = $projTypeLibelle;
}
if (!$change) {

  print "<table class=table_border border=\"0\" cellspacing=\"1\" cellpadding=\"2\">\n";
  print "<tr  class=tr_header>\n";
  print "  <td>Projectid</td>\n";
  print "  <td><a href=\"$scriptname?projects=1&order=name\">Customer</a></td>\n";
  print "  <td>Description</td>\n";
  print "  <td><a href=\"$scriptname?projects=1&order=projectmanager\">Responsable projet</a></td>\n";
  print "  <td>Type de projet</td>\n";
  print "  <td>Membres</td>\n";
  print "</tr>\n";
  $SQL = qq|Select p.projid, c.name, p.projectmanager, p.description, t.libelle, c.custid
            From customers c, projects p
			Left join project_type t On p.project_type_id = t.id
            Where (p.custid = c.custid) $filter
            Order By $order|;
  &SQL_Execute($SQL);
  while ( my($proj, $kund, $ledare, $beskr, $project_type, $kundnr) = &SQL_Fetch() ) {
    print "<tr>\n";
    print "  <td><a href=\"$scriptname?projects=1&change=$proj\">$proj</a></td>\n";
    print "  <td>$kund</td>\n";
    print "  <td>$beskr</td>\n";
    print "  <td>$EmpNames{$ledare}</td>\n";
	print "  <td>$project_type</td>\n";

    my $count = $pCount{$proj} + 0;
    print "  <td>&nbsp;<a href=\"$scriptname?projektdeltagare=$proj\">$count</a></td>\n";
    print "</tr>\n";
  }
  print "</table>\n";
  print "</td></tr></table>\n";
  $layer = "layer1";
} else {
  $SQL = qq|Select p.projid, c.name, p.projectmanager, p.description, c.custid,
                   activities_mask, closed, overtime1, overtime2, project_type_id
            From projects p, customers c
            Where (p.custid = c.custid) And (p.projid = '$change')|;
  &SQL_Execute($SQL);
  @projch = &SQL_Fetch();
  if ( $projch[6] ) {
    $avslut = "checked";
    $titel_avslut = " [avslutat projekt]";
  }
  $ledare = $projch[2];
  $layer = "layer2";
  $project_type = $projch[9];
}

if ( $change ) { $form_title = "Ändra projekt"; }
else           { $form_title = "Nytt projekt"; }

print "<div id=\"$layer\">\n";

print "<form method=\"post\" name=\"NyttProjekt\" action=\"$scriptname?projects=1&sparaproj=1\">\n";
print "<br><table border=\"0\" cellspacing=\"1\" cellpadding=\"2\" class=table_form>\n<tr><td>\n";

print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" class=title_bar>\n";
print " <tr>\n";
print "   <td>&nbsp;$form_title</td>\n";
print "   <td align=\"right\" valign=\"middle\"><a href=# onclick=\"helpWin('Projekt')\"><img src=\"$RELPATH/question.gif\"width=\"16\" height=\"16\" border=\"0\" alt=\"Help\"></a><a href=\"#\" onClick=\"javascript:hideLayer('$layer')\"><img src=\"$RELPATH/kryss.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"St&auml;ng ned formul&auml;r\"></a></td>\n";
print " </tr>\n";
print "</table>\n";

print "<table border=0 cellspacing=5 cellpadding=0 ><tr>\n";

if (!$change) {
  print "<td colspan=\"2\">Projectid: <input type=\"text\" name=\"projnr\" size=\"4\" maxlength=\"4\" class=\"ari2\" value=\"$projch[0]\" onblur=\"clear_statusbar(this.form)\"></td></tr><tr>\n";
}
else {
  print "<td>Projectid: <input type=\"text\" name=\"projnr\" size=\"4\" maxlength=\"4\" class=\"ari2disabled\" value=\"$projch[0]\" disabled></td>\n";
  print "<td colspan=\"2\">Marquer clos <input type=\"checkbox\" name=\"avslut\" value=\"1\" $avslut onClick=avslutaProjekt()></td></tr><tr>\n";

  shift @kunder;
  shift @ProjLeaders;
}

print "<td>Customer<br><select name=\"kundnr\" onblur=\"clear_statusbar(this.form)\">\n";

foreach ( @kunder ) {
	my $sel;
  if ($change && $_ == $projch[4]) {
  	$sel = 'selected';
  }
  print "    <option $sel value=\"$_\">$kundnamn{$_}</option>\n";
}
print "</select></td>\n";
print "<td colspan=\"2\">Description<br><input type=\"text\" name=\"beskrivning\" size=\"40\" maxlength=\"40\" class=\"ari2\" value=\"$projch[3]\" onblur=\"clear_statusbar(this.form)\"></td>\n";
print "<tr>\n";
print "<td>Responsable du projet<br><select name=\"ledare\" onblur=\"clear_statusbar(this.form)\">\n";

foreach ( @ProjLeaders ) {
  my $sel;
  if ($change && $_ == $ledare) {
  	$sel = 'selected';
  }
  print "    <option $sel value=\"$_\">$EmpNames{$_}</option>\n";
}
print "</select></td>\n";

print "<td align=right>Overtime1<br><input type=\"text\" name=\"overtid1\" size=\"4\" maxlength=\"4\" class=\"ari2\" value=\"$projch[7]\" onblur=\"clear_statusbar(this.form)\"></td>\n";
print "<td>Overtime2<br><input type=\"text\" name=\"overtid2\" size=\"4\" maxlength=\"4\" class=\"ari2\" value=\"$projch[8]\" onblur=\"clear_statusbar(this.form)\"></td>\n";
print "</tr>\n";

print "<tr>\n";
print "<td colspan=\"3\">Type de projet <select name=\"project_type\">";
while ( my ($key, $value) = each(%projTypes) ) {
  my $sel;
  if ($change && $key == $project_type) {
  	$sel = 'selected';
  }
  print "    <option $sel value=\"$key\">$value</option>\n";
}
print "</select></td>\n";
print "</tr>\n</table>\n";

print "<table border=0 cellspacing=5 cellpadding=0>\n<tr>\n";
print "<td>Activités<br>\n";
print "<select name=menu1 size=7 multiple style=\"width:165px; font-size:10px; height: 100px\" onClick=\"one2two()\">\n";
print "</select>\n";
print "</td><td align=center>\n<input type=\"button\" onClick=\"one2two()\" value=\" >> \"><br><br>\n";
print "           <input type=\"button\" onClick=\"two2one()\" value=\" << \" >\n</td><td>\nActivités list<br>\n";
print "<select name=menu2 size=7 multiple style=\"width:165px; font-size:10px; height: 100px\" onClick=\"two2one()\">\n";
print "</select><input type=\"hidden\" name=\"mask\" value=\"$projch[5]\"></td></tr>\n";

print "<tr align=\"right\">\n";
print " <td colspan=\"3\"><br><input type=\"submit\" name=\"submit\" value=\"OK\"  onClick=\"submitData(event, validera_projekt())\"></td>\n";
print "</tr>\n";
print "<tr>\n";
print " <td colspan=\"3\"><div id=statusbar2>&nbsp;</div></td>\n";
print "</tr>\n";
print "</table>\n";

if ($change) {
  print "<input type=\"hidden\" name=\"change\" value=\"$change\">\n";
}
print "</td></tr></table>\n";

print "</form>\n";
print "</div>\n";

print "<SCRIPT>\nvar tasksV = [\n";
$SQL = qq|Select actid, name, maskid
          From activities
          Order By name|;
&SQL_Execute($SQL);
while ( my($lopnr, $namn, $mask)  = &SQL_Fetch ) {
  print "\t[$lopnr, '$namn', $mask],\n"
}
print "\t[0, '', 0]\n];\n";

print qq|
var opt1 = document.NyttProjekt.menu1;
var opt2 = document.NyttProjekt.menu2;

var i1 = 0;
var i2 = 0;

var mask = document.NyttProjekt.mask.value;

for (i=0; i<tasksV.length-1; i++) {
	if ((tasksV[i][2] & mask) != 0)
		opt1.options[i1++] = new Option(tasksV[i][1], tasksV[i][0]);
	else
		opt2.options[i2++] = new Option(tasksV[i][1], tasksV[i][0]);
}

</SCRIPT>
|;

}

######################################################################################

sub SparaVecka {

my $week = $form{'week'} || 0;
my $year = $form{'year'} || $lastyear;

$SQL = qq|Delete From reports_$year
          Where (week = $week) And (userid = $user)|;
&SQL_Execute($SQL);

my($timmar, $i);
for $i(1..$row_number) {
  if ( $form{'projnr'.$i} ) {
  	my $theme = $form{'theme'.$i} || ' ';
        my $commentaire = $form{'commentaire'.$i} || ' ';
  	#$theme =~ tr/'"/´´/;
  	$theme =~ s/'/''/g;
        $commentaire =~ s/'/''/g;

    my @row = ($form{'projnr'.$i},$form{'lopnr'.$i},$form{'tidkodnr'.$i},$form{'mon'.$i},$form{'tues'.$i},$form{'wednes'.$i},$form{'thurs'.$i},$form{'fri'.$i},$form{'satur'.$i},$form{'sun'.$i}, $theme, $commentaire);
    $timmar += $row[3]+$row[4]+$row[5]+$row[6]+$row[7]+$row[8]+$row[9];
    $row[1] ||= 0;   # om av någon anledning ej löpnr och debnr
    $row[2] ||= 0;   # sparats så lagras 0
    for (3..9) {
    	$row[$_] =~ tr/,/./;  # decimalkomma -> punkt
      $row[$_] ||= '0' # om mån-sön ej har värden (NULL) så lagras 0
    }
    $SQL = qq|Insert Into reports_$year (userid,week,row,projid,actid,timecode,monday,tuesday,wednesday,thursday,friday,saturday,sunday,theme,commentaire)
              Values ($user,$week,$i,$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],'$row[10]','$row[11]')|;

    &SQL_Execute($SQL);
	}
}

my $gammal_vecka = ($week < $lastweek && $year == $lastyear) || ($year < $lastyear);

if ( $form{'finished'} && $gammal_vecka) {
  # summerar fram tidbanken istället
  my $tidbank = $form{'orig_tidbank'} + $timmar - $form{'arbetstid'};
  # spara veckan i weeks_xxxx, lämnar kontroll tomt
  $SQL = qq|Insert Into weeks (year, week, userid, timebank)
            Values ($year, $week, $user, $tidbank)|;
  &SQL_Execute($SQL);
  if ($week == 52) {
  	$SQL = qq|Update users
  						Set current_year = $year + 1
  						Where userid = $user|;
  	&SQL_Execute($SQL);
  }

}

}

#########################################################################

sub VisaTidrapporter {

my $empo = $qform{'empid'};
&NamnLista('', !$empo);

my $year = $qform{ 'year' } || $lastyear;
my($week1, $week2);
if ($qform{'markera'}) {
  $week1 = $form{'week1'};
  $week2 = $form{'week2'};
  $year = $form{'year'};

  $empo  = $form{'empo'};
  my($veckolista);
  for ($week1..$week2) {
    if ( $form{ 'kollat'.$_ } ) {
      $veckolista .= $_.',';
    }
  }
  if ( $veckolista ) {
    chop($veckolista);
    $SQL = qq|Update weeks
              Set checked_by = $admin
              Where (userid = $empo) And week In ($veckolista) AND year = $year|;
    &SQL_Execute($SQL);
  }
}
else {
  $week1 = $qform{'week1'};
  $week2 = $qform{'week2'};
  $year = $qform{'year'};
}

print "<form method=\"get\" name=\"admin_change\" action=\"$scriptname\">\n";
print "<input type=\"hidden\" name=\"reports\" value=\"1\">\n";

print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" class=table_border_form>\n";
print "<tr><td>Sélectionner le salarié et l'année pour générer le report</td></tr>\n";
print "<tr><td>Salarié:\n";
print "  <select name=\"empid\">\n";
foreach (@emps) {
  if ($_ == $empo) { print "    <option selected value=\"$empo\">$EmpNames{$empo}</option>\n"; }
  else             { print "    <option value=\"$_\">$EmpNames{$_}</option>\n"; }
}
print "  </select>\n";
###############
&PeriodSelect($week1,$week2, $year);
###############

print "  <input type=\"submit\" name=\"Submit\" value=\"  OK  \" >\n";
print "</td></tr></table>\n";

print "</form>\n";

if (!$empo) {
  &TidrapportStatus;
  return;
}

# skriv ut tidkort i intervalet v1 - v2
&UserTidKort($empo, $week1, $week2, $year);

}


######################################################################################

sub PrintAdminMenu {

my $superUser = ''; #($user == 666) ? "\nwriteMenu('green','super=1','Admin','Pour superuser')" : '';

my $name = $username || $cookies{'name'};

$_ = <<EOP;

<SCRIPT language=JavaScript src="$RELPATH/rodeoadm.js"></SCRIPT>

<!--<body bgcolor="#FFFF99"  marginwidth=30 marginheight=0 topmargin=0 leftmargin=30 rightmargin=0 bottommargin=0
style="background: #FFFF99 url($RELPATH/photo.jpg) bottom right no-repeat fixed"
>//-->
<body marginwidth=30 marginheight=0 topmargin=0 leftmargin=30 rightmargin=0 bottommargin=0
style="background: url($RELPATH/photo.jpg) bottom right no-repeat fixed"
>

<table border="0" cellspacing="2" cellpadding="0" ID="adminMenu">
  <tr>
<script language="javascript">
<!--$superUser
  writeMenu('red','showemps=1', "Salariés", "Ajout/modif détails du salarié");
  writeMenu('red','kunder=1',   "Customers",    "Ajout/modif customers");
  writeMenu('red','projects=1', "Projets",   "Information du projets");
  writeMenu('red','lopnummer=1',"Activités", "Activités des projets");
  writeMenu('red','almanacka=1','Calendrier',  "heures hebdo annuelles, jours fériés ...");

  writeMenu('green','reports=1','Time&nbsp;reports',   'Timereports');
  writeMenu('green','faktura=1','Temps&nbsp;&nbsp;projets',        'Temps passés par projets');
  writeMenu('green','allafakturor=1','Facturation', 'Paiements dûs/reçus pour comptabilité');
  writeMenu('green','prognos=1','Prévisions',        'Estimation des futures facturation et des heures travaillées');

  writeMenu('red','logout=1', "Déconnexion",         "Dégage !");
-->
</script>

    <td width="20"></td>
    <td  id="inloggad">Connecté:<br>$name</td>
  </tr>
</table>
<DIV ID="container">

EOP
print;

}

##########################################################################

sub Datum2month($$$) {

my ($vecka1, $vecka2, $year) = @_;

my $Jan1 =  $dayOfJan1[$year - 2000];
$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår

for (1..12) {
  $months[$_] = $months[$_] + $months[$_ - 1];
}

my $w1 = ($vecka1)*7 - $Jan1 - 1;
#my $w2 = $w1 + 6;
my $w2 = ($vecka2)*7 - $Jan1 - 1 + 6;

my $i = 0;
while ($w1 > $months[$i] && $i < 13) {
   $i++;
}
my $monad = $i;
if ($monad == 0) {
  $monad = 1;
}

my $day =  $w1 - $months[$monad-1];
my $fd;
if ($day<1) {
	my $ly = $year - 1;
	$fd = -1;
}
else {
	$fd = $monad;
}

# return ($fd) if (!$params[1]);
while ($w2 > $months[$i] && $i < 13) {
   $i++;
}
$monad = $i;
if ($monad == 0) {
  $monad = 1;
}
$day = $w2 - $months[$monad-1];
my $nd = $monad;

my $datum2 = $nd;
my $valid = ($fd == $nd);

return ($datum2, $valid);

}

##########################################################################

sub Datum($$$) {

my ($vecka1, $vecka2, $year) = @_;

my $Jan1 =  $dayOfJan1[$year - 2000];
$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår

for (1..12) {
  $months[$_] = $months[$_] + $months[$_ - 1];
}

#my $w1 = ($vecka1)*7 - $Jan1 - 1;
#my $w2 = ($vecka2)*7 - $Jan1 - 1 + 6;
my $w1 = ($vecka1 - 1) * 7 + $Jan1 - 3;
my $w2 = ($vecka2 - 1) * 7 + $Jan1 - 3 + 6;

my $i = 0;
while ($w1 > $months[$i] && $i < 13) {
   $i++;
}
my $monad = $i;
if ($monad == 0) {
  $monad = 1;
}

my $day =  $w1 - $months[$monad-1];
my $fd;
if ($day<1) {
	my $ly = $year - 1;
	$fd = "<font style=\"font-size: 12px\">31/12 ($ly)</font>";
}
else {
	$fd = "$day/$monad";
}

# return ($fd) if (!$params[1]);
while ($w2 > $months[$i] && $i < 13) {
   $i++;
}
$monad = $i;
if ($monad == 0) {
  $monad = 1;
}
$day = $w2 - $months[$monad-1];
my $nd = "$day/$monad";
my $datum2 = "$fd - $nd";

return ($datum2);

}

#############################################################################

sub PrintKunder {

my $order = $qform{'order'} || 'custid';

my $sparakund = $qform{'sparakund'};
if ( $sparakund ) {
  my $kundnr = $form{'kundnr'};
  my $kund   = $form{'kund'} || ' ';
  my $beskr  = $form{'beskrivning'} || ' ';
  if (my $change = $form{'change'}) {
    $SQL = qq|Update customers
              Set name = '$kund', description = '$beskr'
              Where (custid = $change)|
  }
  else {
    goto SKIPSPARA if (!$kundnr);
    $SQL = qq|Insert Into customers (custid, name, description)
              values ($kundnr, '$kund', '$beskr')|;
  }
  &SQL_Execute($SQL);
}
SKIPSPARA:

my $change = $qform{'change'};

my($layer, $form_title, $disabled, @kundch);

print qq|<h2>Customers</h2>\n
         <div class="td_spalt">Add new customer or click cutomerid to edit details.
          Note: customerids are unique and can not be changed</div><br>|;

if (!$change) {
  print "<input type=\"button\" name=\"Input\" value=\"New customer\"  onClick=\"javascript:showLayer('layer1')\"><br><br>\n";

  print "<table class=table_border border=\"0\" width=\"450\" cellspacing=\"1\" cellpadding=\"2\">\n";
  print "<tr class=tr_header>\n";
  print "  <td>Customerid</td>\n";
  print "  <td><a href=\"$scriptname?kunder=1&order=name\">Name</a></td>\n";
  print "  <td>Description</td>\n";
  print "</tr>\n";

  $SQL = qq|Select custid, name, description
            From customers
            Order By $order|;
  &SQL_Execute($SQL);
  while ( my($kundnr, $kund, $beskr)  = &SQL_Fetch() ) {
    print "<tr>\n";
    print "  <td><a href=\"$scriptname?kunder=1&change=$kundnr\">$kundnr</a></td>\n";
    print "  <td>$kund</td>\n";
    print "  <td>$beskr</td>\n";
    print "</tr>\n";
  }
  print "</table>\n";

  $visible = "hidden";
  $form_title = "Ny kund";
  $layer = "layer1";
}
else {
  $SQL = qq|Select custid, name, description
            From customers
            Where custid = '$change'|;
  &SQL_Execute($SQL);
  @kundch = &SQL_Fetch();
  $visible = "normal";
  $form_title = "Ändra kund";
  $disabled = "disabled";
  $layer = "layer2";
}

print qq|
<div id=$layer >
<form method=post name="NyKund" action="$scriptname?kunder=1&sparakund=1">

<table border=0 cellspacing=1 cellpadding=2  class="table_form">
<tr>
<td bgcolor=lightgrey>

	<table border=0 cellspacing=0 cellpadding=0 width="100%" class=title_bar>
	 <tr>
	   <td >&nbsp;$form_title</td>
	   <td  align=right valign=middle><a href=# onClick=javascript:hideLayer('$layer')><img src="$RELPATH/kryss.gif" width=16 height=16 border=0 alt="St&auml;ng ned formul&auml;r"></a></td>
	 </tr>
	</table>

	<table border=0 cellspacing=5 cellpadding=0>
	<tr>
		<td>Customerid: <input type=text name=kundnr size=4 maxlength=4 class="ari2$disabled" value="$change" $disabled></td>
		<td></td>
	</tr>
	<tr>
		<td>Name:<br><input type=text name=kund size=30 maxlength=30 class=ari2 value="$kundch[1]"></td>
		<td>Description:<br><input type=text name="beskrivning" size=40 maxlength=40 class=ari2 value="$kundch[2]"></td>
	</tr>
	<tr>
		<td colspan=2 align=right><input type=submit name=submit value="OK" onClick="submitData(event, validera_kunder())"></td>
	</tr>
	<tr>
		<td colspan=2><input type=text name=statusbar size=80 style="font-size: 11px; font-family: arial;  font-weight: bold; border-width:1; border-style: normal; border-color:#000000;  background-color: lightgrey;  color: red; locked"></td>
	</tr>
|;

if ($change) {
  print "<input type=\"hidden\" name=\"change\" value=\"$change\">\n";
}
print "</table>\n";
print "</td></tr>\n</table>\n";

print "</form></div>\n";

}

####################################################################################

sub Fakturera {

&NamnLista;

my $behorighet = ($AccessRight == 2);
my($filter);

my $year = $form{'year'} || $qform{'year'} || $lastyear;
my $month = $form{'month'} || $qform{'month'};

if ( $qform{'received'} ) {
	my $unik            = $form{'ID'};
	my $paymentreceived = $form{'date'};
	$paymentreceived =~ s/^(....)(..)(..)/$1-$2-$3/;
	$SQL = qq|Update invoices_$year
    	      Set payment_received = '$paymentreceived'
      	    Where ID = '$unik'|;
  	&SQL_Execute($SQL);
}

if ( my $unik = $qform{'remove'} ) {
  $SQL = qq|DELETE FROM  invoices_$year
            Where (ID = '$unik')|;
  &SQL_Execute($SQL);
  $filter = 1;
}
elsif ( $qform{'betalda'} ) {
  my $rader  = $form{'rader'};
  my($day, $month, $year) = @times[3, 4, 5];

  if ($day < 10)   { $day = '0'.$day; }
  if ($month < 10) { $month = '0'.$month; }
  my $datum = "$year-$month-$day";
  my $uniks;
  for (0..$rader) {
    if ( my $unik = $form{ 'faktura'.$_} ) {
      $uniks .= "'$unik',";
    }
  }
  if ($uniks) {
  	chop $uniks;
  	$SQL = qq|Update invoices_$year
    	        Set posted_by = $admin, date_posted = '$datum'
      	      Where ID In ($uniks)|;
  	&SQL_Execute($SQL);
  }
  $filter = 1;
}

$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår

print "<form method=\"get\"  action=\"$scriptname\">\n";
print "<input type=\"hidden\" name=\"allafakturor\" value=\"1\">\n";
print "<table border=\"0\" cellspacing=\"0\" width=\"350\" cellpadding=\"4\" class=table_form>\n";
print "<tr><td>Afficher les heures sélectionnées<br> pour la facturation (tous projets)</td>\n";
print "<td align=\"right\">\n";
#&PeriodSelect($week1,$week2,$year, 1);  # no week2
print "<select name=\"month\">\n";
for (1..12) {
	my $sel = ($_ == $month) ? 'selected' : '';
  print "  <option $sel value=\"$_\">$Months_W[$_]</option>\n";
}
print "</select>\n";
print "  <input type=\"submit\" name=\"Submit\" value=\"OK\" >\n";
print "</td></tr></table>\n";

print "</form>\n";

my($sql_filter, $time_filter);

if ($MONTH_REPORTS) {
	$time_filter = "month >= $month";
} else {

	my $w1 = (&Month2Week($month, $year))[0] || 	1;

	#print "[$w1, $month, $year]";
	$time_filter = "week1 >= $w1";
}


$year ||= $lastyear;
if  ( !$month ) {
#  Direkt ingång med knapp

	my $tfilter = $MONTH_REPORTS ? 'month' : 'week1';
  $SQL = qq|Select Min($tfilter)
            From invoices_$year
            Where payment_received IS NULL|;
  &SQL_Execute($SQL);
  while ($month = &SQL_Fetch ) {};
  $month ||= 1;
  $filter = 1;
  $sql_filter = "And payment_received Is NULL";
  print "<h2>Aucunes factures payées\n";
  print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('Fakturor')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";
}

my $removestring = "year=$year&month=$month";

if (!$filter) {
  print "<h2>Factures de $Months_W[$month], $year\n";
  print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('Fakturor')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";
}

print "<form method=\"post\" name=\"form1\" action=\"$scriptname?allafakturor=1&betalda=1\">\n";

print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=table_invoice>\n";
print "<tr class=tr_header>\n";
print "  <td>Projet</td>\n";
if ($MONTH_REPORTS) { print "  <td>Mois</td>\n"; }
else                { print "  <td>Semaines</td>\n"; }
print "  <td>Montant [\€]</td>\n";
print "  <td>Heures</td>\n";
print "  <td>Créée&nbsp;par</td>\n";
print "  <td>Créée</td>\n";
print "  <td>Envoyée&nbsp;par</td>\n";
print "  <td>Envoyée</td>\n";
print "  <td>Paiement&nbsp;dû</td>\n";
print "  <td>Paiement&nbsp;reçu</td>\n";
print "</tr>\n";


$SQL = qq|Select p.projid, p.description, week1, week2, month, ft.hours, created_by, date_created,
                 posted_by, date_posted, ID, payment_due, payment_received, c.name, amount
          From invoices_$year ft, projects p, customers c
          Where ($time_filter) And (p.custid = c.custid) And (p.projid = ft.projid)
          $sql_filter
          Order By p.projid, week1|;
&SQL_Execute($SQL);
my($visaknapp, $checkbox, $oldproj, $spann, $month, $belopp, $rader, $i, $oldprojekt, $proj_beskr, $summa);
while ( my ($projekt, $beskrivning, $week1, $week2, $month, $timmar, $signatur, $created, $postedby, $posted, $unik, $paymentdue, $paymentreceived, $kund, $belopp) = &SQL_Fetch() ) {
	$summa += $belopp;
	$belopp = &Format($belopp);
	$i++;

	if ($oldproj && $oldproj != $projekt) {
    print "<tr class=tr_header><td colspan=\"10\"></td></tr>\n";
  }
  $oldproj = $projekt;

	print "<tr>\n";
	if ($projekt != $oldprojekt) {
    $oldprojekt = $projekt;
    $proj_beskr = "$projekt&nbsp;$kund&nbsp;-&nbsp;$beskrivning";
    print "  <td class=td_invoice><b>$proj_beskr</b></td>\n";
  }
  else {
  	print "  <td class=td_invoice>&nbsp;</td>\n";
  }

  if ( !$posted && $behorighet ) {
    $visaknapp = 1;
    $checkbox = "<input type=\"checkbox\" name=\"faktura$i\" value=\"$unik\"><font color=\"red\"><b>Posted</b></font>";
    $posted = "<a href=# onclick=\"removeInvoice('$scriptname?allafakturor=1&$removestring&remove=$unik')\">Supprimer...</a>";
  }
  else {
  	my $invoice_besk = "$proj_beskr, ";
  	$invoice_besk .= $MONTH_REPORTS ? $Months_W[$month] : "semaines: $week1-$week2";

    $checkbox = "$EmpNames{$postedby}";
    if ($behorighet && !$paymentreceived) {
    	$paymentreceived = "<a href=# onclick=\"setReceived('$unik', '$invoice_besk')\">Set...</a>";
    }
  }
  $paymentreceived ||= '&nbsp;';
  $checkbox ||= '&nbsp;';
  $posted ||= '&nbsp;';

  if ($MONTH_REPORTS) {
    $spann = $Months_W[$month];
    $month = "&month=$month";
  }
  else {
    $spann = "$week1 - $week2";
    $month = '';
  }

  $spann = "<a href=\"$scriptname?faktura=1&projnr=$projekt&week1=$week1&week2=$week2&year=$year$month\">$spann</a>";

  print "  <td>$spann</td>\n";
  print "  <td align=right><b>$belopp</b></td>\n";
  print "  <td align=right>$timmar</td>\n";
  print "  <td>$EmpNames{$signatur}</td>\n";
  print "  <td>$created</td>\n";
  print "  <td>$checkbox</td>\n";
  print "  <td>$posted</td>\n";
  print "  <td>$paymentdue</td>\n";
  print "  <td>$paymentreceived</td>\n";
  print "</tr>\n";
}
print "</table>\n";

$summa = &Format($summa);
print "<p>Somme totale <b>$summa\€</b></p>";

if ($visaknapp && $behorighet) {
  print "<br><input type=\"submit\" name=\"submit\" value=\"Marquer les entrées comme envoyées\" >\n";
  print "<input type=\"hidden\" name=\"rader\" value=\"$i\">\n";
  print "<input type=\"hidden\" name=\"month\" value=\"$month\">\n";
  print "<input type=\"hidden\" name=\"year\" value=\"$year\"><br>\n";
}

print "</form>\n";

print qq|
<div id=layer1 >
<form method=post name="paymentreceived" action="$scriptname?allafakturor=1&received=1">
<input type=hidden name=ID >
<input type=hidden name=year value="$year" >
<table border=0 cellspacing=1 cellpadding=2  class="table_form" width=300>
<tr>
<td>
	<table border=0 cellspacing=0 cellpadding=0 width="100%" class=title_bar>
	 <tr>
	   <td>&nbsp;Enregistrer les Paiements reçus</td>
	   <td  align=right valign=middle><a href=# onClick=javascript:hideLayer('layer1')><img src="$RELPATH/kryss.gif" width=16 height=16 border=0 alt="St&auml;ng ned formul&auml;r"></a></td>
	 </tr>
	</table>
	<table border=0 cellspacing=5 cellpadding=0 width=100%>
	<tr>
		<td>Invoice:</td>
		<td id="td_invoice">&nbsp;</td>
	</tr>
	<tr>
		<td>Date:</td>
		<td><input type=text name="date" size=10 maxlength=10 class=ari2 onBlur="clear_statusbar(this.form)"></td>
	</tr>
	<tr>
		<td colspan=2 align=right><input type=submit name=submit value=OK onClick="submitData(event, validera_received())"></td>
	</tr>
	<tr>
		<td colspan=2><div id=statusbar2>&nbsp;</div></td>
	</tr>
	</table>
</tr>
</table>
</div>

</form>
|;

#print "<p><a class=\"a3\" href=\"$scriptname?printer=1&week1=$week1&week2=$week2&year=$year\" target=\"new\">Ouvrir une version imprimable dans une nouvelle fenêtre.</a></p>\n";

}

##########################################################

sub TidrapportStatus {

# få reda på senast rapporterade vecka
$SQL = qq|Select userid, Max(week)
          From reports_$lastyear
          Group By userid|;
&SQL_Execute($SQL);
my %senast_rapporterade;
while ( my ($user, $vecka) = &SQL_Fetch() ) {
  $senast_rapporterade{$user} = $vecka;
}

$SQL = qq|Select userid, Max(week)
          From weeks
					WHERE year = $lastyear
          Group By userid|;
&SQL_Execute($SQL);
my %senast_kontrollerade;
while ( my ($user, $vecka) = &SQL_Fetch() ) {
  $senast_kontrollerade{$user}  = $vecka;
}

print "<h2>Timereporting status $lastyear\n";
print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('TidrapportStatus')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";


print "<table class=table_border border=\"0\" cellspacing=\"1\" cellpadding=\"2\">\n";
print "<tr class=tr_header>\n";
print "  <td rowspan=\"2\">Salariés</td>\n";
print "  <td colspan=\"3\" align=\"center\">Dernière ...</td>\n";
print "</tr>\n";
print "<tr class=tr_header>\n";
print "  <td width=\"90\">sem. remplie</td>\n";
print "  <td width=\"90\">sem. close</td>\n";
print "  <td width=\"90\">connexion</td>\n";
print "</tr>\n";

my $year = $lastyear;

shift @emps; # plocka bort superuser
my $empid;
foreach $empid(@emps) {
  next if ($empid == 666);
  my $name = $EmpNames{$empid};

  my $week1 = $senast_kontrollerade{$empid};
  my $week2 = $senast_rapporterade{$empid};
  print "<tr>\n";
  print "  <td><a href=\"$scriptname?visayear=$empid\">$empid - $name</a>&nbsp;</td>\n";

  my $senast = ($week1 < $week2) ? $week1 + 1 : $week1;

  print "  <td align=\"center\"><a href=\"$scriptname?reports=1&empid=$empid&week1=$senast&week2=$week2&year=$year\"><b>$week2</b></a>&nbsp;</td>\n";

  if ($week1) { print "  <td align=\"center\"><a href=\"$scriptname?reports=1&empid=$empid&week1=$week1&week2=$week1&year=$year\"><b>$week1</b></a>&nbsp;</td>\n"; }
  else        { print "  <td align=\"center\">-</td>\n"; }

  my $inl;
  if ( $inl = $inloggad{$empid} ) {
    $inl = ($abstime - $inl < $TIME_LIMIT) ? "<font color=red>".&min2Date($inl)."</font>" : &min2Date($inl);
  }
  else {
    $inl = '-';
  }
  print "  <td align=\"center\">$inl</td>\n";
  print "</tr>\n";
}
print "</table></td></tr></table>\n";

}

##################################################################

sub ProjektStatus {

my $year = $qform{'year'} || $lastyear;
# måste ha hela tuplen där week2 = Max
$SQL = qq|Select p.projid, week1, week2, month, date_created
          From invoices_$year i, projects p
          Where (closed Is NULL) And (i.projid = p.projid)
          Order By p.projid|;
&SQL_Execute($SQL);
my(%projweek2, %projweek1, %projbetald, %projmonth);
while ( my ($projnr, $week1, $week2, $month, $betald) = &SQL_Fetch() ) {
  if ($week2 > $projweek2{$projnr}) {
    $projweek2{$projnr} = $week2;
    $projweek1{$projnr} = $week1;
    $projbetald{$projnr} = $betald;
    $projmonth{$projnr} = $month;
  }
}

print "<h2>Etat des projets sur $year\n";
print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('ProjektStatus')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";


$SQL = qq|Select projid, Max(week)
          From reports_$year
          Group By projid|;
&SQL_Execute($SQL);
my %projekt_max;
while ( my ($projnr, $week) = &SQL_Fetch() ) {
  $projekt_max{ $projnr } = $week;
}

print "<table class=table_border border=\"0\" cellspacing=\"1\" cellpadding=\"2\" >\n";
print "<tr class=tr_header>\n";
print "  <td rowspan=\"2\" width=\"150\">Projet</td>\n";
print "  <td align=\"center\" colspan=\"3\">Dernière ...</td>\n";
print "</tr>\n";
print "<tr class=tr_header>\n";
print "  <td>sem. remplie</td>\n";
print "  <td>période facturée</td>\n";
print "  <td width=\"70\">créée</td>\n";
print "</tr>\n";


$SQL = qq|Select p.projid, c.name, p.description
          From projects p, customers c
          Where (closed Is NULL) And (p.custid=c.custid)
          Order By p.projid|;
&SQL_Execute($SQL);
while ( my ($projnr, $kund, $beskrivning) = &SQL_Fetch() ) {
  my ($projfirst,$projsist,$projbet) = ($projweek1{$projnr}, $projweek2{$projnr}, $projbetald{$projnr},);
  my $week = $projekt_max{$projnr};
  my $lastperiod = $projweek2{$projnr} + 1;
  if ($lastperiod > $week) {
    $lastperiod = $week;
  }
  my($month, $spann);
  if ($MONTH_REPORTS) {
    $month = $projmonth{$projnr};
    $spann = "month=$month&year=$year\">$Months_W[$month]";
  }
  else {
    $spann = "week1=$projfirst&week2=$projsist&year=$year\"><b>$projfirst - $projsist";
  }
  print "<tr>\n";
  print "  <td><a href=\"$scriptname?visaprojyear=$projnr&year=$year\">$projnr - $kund $beskrivning</a></td>\n";
  if ( $lastperiod ) { print "  <td align=\"center\"><a href=\"$scriptname?faktura=1&projnr=$projnr&week1=$lastperiod&week2=$week&year=$year\"><b>$week</b></a></td>\n"; }
  else               { print "  <td align=\"center\">Aucune heure</td>\n"; }
  if ( $projsist  )  { print "  <td align=\"center\"><a href=\"$scriptname?faktura=1&projnr=$projnr&$spann</a></td>\n"; }
  else               { print "  <td align=\"center\">Aucune heure</td>\n"; }
  print "  <td align=\"center\">$projbet</td>\n";
  print "</tr>\n";
}
print "</table>\n";

}

#######################################################################
sub TimStapel($$) {

my($projnr, $summa) = @_;

my $SQL = qq|Select timmar
             From project
             Where  projektnr = $projnr|;
&SQL_Execute($SQL);
my $best = &SQL_Fetch();

print "<p>Upparbetade timmar (Horaire)</p>\n";

my $w = int($summa/$best*500);
my $w2 = 500 - $w;
$summa = int($summa);

print "<table width=500 border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>\n";
print "<td width=\"4\" rowspan=\"2\"  bgcolor=\"black\">&nbsp;</td>\n";
print "<td bgcolor=\"green\" width=\"$w\">&nbsp;</td><td bgcolor=\"blue\" width=$w2 colspan=2>&nbsp;</td>\n";
print "</tr><tr><td  width=$w bgcolor=\"white\">&nbsp;<font color=\"black\">0</font></td><td bgcolor=\"white\"><font color=\"red\">$summa</font></td><td align=right bgcolor=\"white\"><font color=\"black\">$best</font></td>\n";
print "</tr></table><br>\n";

}


#################################################

sub PeriodSelect($$$$) {

my ($week1, $week2, $year, $noweek2) = @_;

$year ||= $lastyear;

print "  Semaines:\n";
print "  <select name=\"week1\">\n";
for (1..52) {
  if ($_ == $week1) { print "    <option selected value=\"$_\">$_</option>\n"; }
  else              { print "    <option value=\"$_\">$_</option>\n"; }
}
print "  </select>\n";
if (!$noweek2) {
	print "  -\n";
	print "  <select name=\"week2\">\n";
	for (1..52) {
	  if ($_ == $week2) { print "    <option selected value=\"$_\">$_</option>\n"; }
	  else              { print "    <option value=\"$_\">$_</option>\n"; }
	}
	print "  </select>\n";
}
print "  <select name=\"year\">\n";
for ($FIRST_YEAR..$lastyear+1){
	my $sel = ($_ == $year) ? 'selected' : '';
	print "<option $sel value=\"$_\">$_</option>\n";
}
print "  </select>\n";

}

######################################################

sub YearView($) {

my $worker = shift;

my $year = $qform{'year'} || $lastyear;
&YearSelect("visayear=$worker", $year);

&NamnLista($worker);

my $SQL = qq|Select Distinct(projid)
             From reports_$year
             Where userid = $worker
             Order by projid|;
&SQL_Execute($SQL);

my(@projekt_lista, @vecko_rader, @vecko_rader_norm, @vecko_rader_not_norm, $antal_projekt, %proj_index);
while ( my $projnr = &SQL_Fetch() ) {
  push(@projekt_lista,$projnr);
  @vecko_rader[++$antal_projekt] = [0,0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0];
  @vecko_rader_norm[$antal_projekt] = [0,0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0];
  @vecko_rader_not_norm[$antal_projekt] = [0,0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0,0,0,0,0,0,0,0,0,
                                      0,0];
  $proj_index{$projnr} = $antal_projekt;
}
my $projlista  = join(',',@projekt_lista);

if ( $projlista eq '' ) {
  print "<h3>$EmpNames{$worker} n'a pas rempli l'année $year</h3>";
  return;
}

my $SQL = qq|Select p.projid, c.name, p.description
             From projects p, customers c
             Where p.custid = c.custid And p.projid In ($projlista)|;
&SQL_Execute($SQL);
my %proj_beskr;
while ( my ($projnr, $kund, $beskrivning) = &SQL_Fetch() ) {
	$kund =~s/ +$//g;
	$beskrivning =~s/ +$//g;
	#my $besk = "$projnr $kund - $beskrivning";
        my $besk = "$projnr - $beskrivning";
	$besk =~ s/ /&nbsp;/g;
  $proj_beskr{$projnr} = $besk;
}

print "<h3>Planning annuel de $EmpNames{$worker} Report annuel pour les projets de $year\n";
print " <a href=\"#\" title=\"Help\" onClick=\"helpWin('YearView')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h3>\n";

print "<table border=\"0\" width=\"1000\" cellspacing=\"1\" cellpadding=\"0\" class=table_border><tr class=tr_bold>\n<td width=300>&nbsp;Projet</td>";
my(@timmar, $i);
for (1..52) {
  print "<td align=\"center\" width=\"10\">$_</td>";
  $timmar[$_] = 0;
}
print "\n</tr>\n";
my @color = ('#80FF80','#FF80C0','#00FFFF','#FFFF00','#80FF80','#FF80C0','#00FFFF','#FFFF00');
$proj_beskr{400} = "Absent";

# Projets & Activités Facturés
print "\n<tr class=\"tr_bold\"><td>-Norm-</td><td colspan=\"52\">&nbsp;</td></tr>\n";
my $SQL = qq|Select projid, actid, week,monday+tuesday+wednesday+thursday+friday+saturday+sunday
             From reports_$year
             Where userid = $worker And timecode = 0|;
&SQL_Execute($SQL);
my @projekt_summa;
while ( my($projnr,$lopnr,$week,$sum ) = &SQL_Fetch() ) {
  $vecko_rader_norm[ $proj_index{$projnr} ][$week] += $sum;
  $projekt_summa[ $proj_index{$projnr} ] += $sum;
}
my($p, $proj);
for ($p=0; $p<=$#projekt_lista; $p++) {
  $proj = $projekt_lista[$p];
  print "<tr>\n  <td>$proj_beskr{$proj}</td>";
  for (1..52) {
    my $timmar = $vecko_rader_norm[ $proj_index{$proj} ][$_];
    $vecko_rader[ $proj_index{$proj} ][$_] += $timmar;
    if ( $timmar != 0 ) { print "<td bgcolor=\"$color[$p]\" align=\"center\" class=td_tabell>$timmar</td>"; }
    else                { print "<td bgcolor=\"white\">&nbsp;</td>"; }
  }
  print "\n</tr>\n";
}

# Projets & Activités Facturés
print "\n<tr class=\"tr_bold\"><td>-OT, no charge-</td><td colspan=\"52\">&nbsp;</td></tr>\n";
my $SQL = qq|Select projid, actid, week,monday+tuesday+wednesday+thursday+friday+saturday+sunday
             From reports_$year
             Where userid = $worker And timecode > 0|;
&SQL_Execute($SQL);
my @projekt_summa;
while ( my($projnr,$lopnr,$week,$sum ) = &SQL_Fetch() ) {
  $vecko_rader_not_norm[ $proj_index{$projnr} ][$week] += $sum;
  $projekt_summa[ $proj_index{$projnr} ] += $sum;
}
my($p, $proj);
for ($p=0; $p<=$#projekt_lista; $p++) {
  $proj = $projekt_lista[$p];
  print "<tr>\n  <td>$proj_beskr{$proj}</td>";
  for (1..52) {
    my $timmar = $vecko_rader_not_norm[ $proj_index{$proj} ][$_];
    $vecko_rader[ $proj_index{$proj} ][$_] += $timmar;
    if ( $timmar != 0 ) { print "<td bgcolor=\"$color[$p]\" align=\"center\" class=td_tabell>$timmar</td>"; }
    else                { print "<td bgcolor=\"white\">&nbsp;</td>"; }
  }
  print "\n</tr>\n";
}

# Affichage du nombre d'heures ouvrées (donc hors JF)
my $SQL = qq|Select hours
          From workhours_$year
          Order by week|;
&SQL_Execute($SQL);
print "<tr class=\"tr_bold\">\n  <td>Heures hebdo</td>";
while ( my($heures_hebdo) = &SQL_Fetch() ) {
  print "<td align=\"center\">$heures_hebdo</td>";
}
print "\n</tr>\n";

print "</table>\n";

&UserPlanering($worker, $year);

}


######################################################

sub ProjectYearView($) {

my $projnr  = shift;

my $year = $qform{'year'} || $lastyear;

&YearSelect("visaprojyear=$projnr", $year);

my $SQL = qq|Select p.description, c.name
             From projects p, customers c
             Where (p.custid = c.custid) And p.projid = $projnr|;
&SQL_Execute($SQL);
my ($beskr, $kund) = &SQL_Fetch();
my $proj_namn = "$beskr - $kund";

my $SQL = qq|Select Distinct(userid)
             From reports_$year
             Where projid = $projnr
             Order by userid|;
&SQL_Execute($SQL);

my(@user_lista, @vecko_rader, %user_index, $antal_users);
while ( my $userid = &SQL_Fetch() ) {
  push(@user_lista,$userid);
  @vecko_rader[++$antal_users] = [0,0,0,0,0,0,0,0,0,0,0,
                                    0,0,0,0,0,0,0,0,0,0,
                                    0,0,0,0,0,0,0,0,0,0,
                                    0,0,0,0,0,0,0,0,0,0,
                                    0,0,0,0,0,0,0,0,0,0,
                                    0,0];
  $user_index{$userid} = $antal_users;
}

print "<h3>Plan for project $projnr ($proj_namn) worked hours year $year\n";
print " <a href=\"#\" title=\"Help\" onClick=\"helpWin('ProjectYearView')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h3>\n";

if ($antal_users) {
  my $userlista  = join(',',@user_lista);
  $SQL = qq|Select userid, name, surname
            From users
            Where userid in ($userlista)|;
  &SQL_Execute($SQL);
  %EmpNames = ();
  while ( my ($userid, $name, $surname, $arv ) = &SQL_Fetch() ) {
  	$name =~s/ +$//g;
  	$surname =~s/ +$//g;
	$surname =~s/#$//g;
    $EmpNames{$userid} = "$name&nbsp;$surname";
  }

  my $SQL = qq|Select userid, week, monday,tuesday,wednesday,thursday,friday,saturday,sunday
               From reports_$year
               Where projid = $projnr|;
  &SQL_Execute($SQL);
  my($summa, $totsum, %user_tot_sum);
  while ( my ($userid,$week,$mon,$tues,$wed,$thur,$fri,$sat,$sun ) = &SQL_Fetch() ) {
    $summa = $mon+$tues+$wed+$thur+$fri+$sat+$sun;
    $totsum += $summa;
    $vecko_rader[ $user_index{$userid} ][$week] += $summa;
    $user_tot_sum{$userid} += $summa;
  }

	print "<table border=\"0\" width=\"1000\" cellspacing=\"1\" cellpadding=\"0\" class=table_border><tr class=tr_bold>\n<td width=\"150\">&nbsp;</td>";
	my @timmar;
	for (1..52) {
	  print "<td align=\"center\" width=\"10\">$_</td>";
	  $timmar[$_] = 0;
	}
	print "\n</tr>\n";

	my @color = ('#80FF80','#FF80C0','#00FFFF','#FFFF00','#80FF80','#FF80C0','#00FFFF','#FFFF00');

  my($u, $i);
	for ($u=0; $u<=$#user_lista; $u++) {
	  my $userid = $user_lista[$u];
	  print "<tr>\n<td>&nbsp;$EmpNames{$userid}</td>";
	  for (1..52) {
	    my $timmar = $vecko_rader[ $user_index{$userid} ][$_];
	    if ( $timmar != 0 ) { print "<td class=\"td_tabell\" bgcolor=\"$color[$u]\" align=\"center\">$timmar</td>"; }
	    else                { print "<td bgcolor=\"white\">&nbsp;</td>"; }
	  }
	  print "\n</tr>\n";
	}
	print "</table>\n";
  print "<br><br>";
	print "<table border=0 cellspacing=1 cellpadding=1 class=table_border>\n";
	  print "<tr class=tr_header><td>User</td>\n";
	  print "    <td width=50>Hours</td>\n";
	  print "    <td width=50>Fee</td>\n";
	  print "    <td width=80>Sum [k\$]</td></tr>\n";
	#$totsum = 0;
	my $u;
	foreach $u(sort {$a<=>$b} keys %user_tot_sum) {
	  my $sum = $user_tot_sum{$u};
	  #$totsum += $sum;
	  print "<tr><td>&nbsp;$EmpNames{$u}</td>\n";
	  print "    <td align=right>&nbsp;$user_tot_sum{$u}</td>\n";
	  print "    <td>&nbsp;$arvode{$u}</td>\n";
	  print "    <td>&nbsp;$sum</td></tr>\n";
	}
	$totsum = &Format(int $totsum);
	print "<tr class=tr_header><td colspan=4 align=center>$totsum tim</td></tr>\n";
	print "</table>\n";

}
else {
  print "<p>No reported hours $year</p>\n";
}

#&TimStapel($projnr,$totsum);
&ProjektPlanering($projnr, $year);

}

##########################################################

sub PrinterFriendly {

&NamnLista;

my $week1 = $qform{'week1'};
my $week2 = $qform{'week2'};
my $year =  $qform{'year'};

print "<h2>Facture pour la semaine $week1, $year</h2>\n";

print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"1\"><tr><td bgcolor=black>\n";
print "<table border=\"0\" cellspacing=\"1\" cellpadding=\"2\">\n";
print "<tr>\n";
print "  <td bgcolor=\"white\"><b>Projet</b></td>\n";
print "  <td bgcolor=\"white\"><b>Mois</b></td>\n";
print "  <td bgcolor=\"white\"><b>Semaine</b></td>\n";
print "  <td bgcolor=\"white\"><b>Valeur</b></td>\n";
print "  <td bgcolor=\"white\"><b>Validé par</b></td>\n";
print "  <td bgcolor=\"white\"><b>Faturé par</b></td>\n";
print "  <td bgcolor=\"white\"><b>Date</b></td>\n";
print "</tr>\n";

$SQL = qq|Select p.projid, p.description, month, week1, week2, ft.hours, created_by, posted_by, c.name, amount
          From invoices_$year ft, projects p, customers c
          Where (week1 >= $week1) And (p.custid = c.custid) And (ft.projid = p.projid)
          Order By p.projid, week1|;
&SQL_Execute($SQL);
while ( my ($projekt, $beskr, $month, $week1, $week2, $timmar, $signatur, $betald, $kund, $belopp) = &SQL_Fetch() ) {
  print "<tr>\n";
  print "  <td height=\"40\">$projekt $kund - $beskr</td>\n";
  print "  <td height=\"40\">$Months_W[$month]</td>\n";
  print "  <td height=\"40\">$week1 - $week2</td>\n";
  print "  <td height=\"40\"><b>$belopp \€ ($timmar h)</b></td>\n";
  print "  <td height=\"40\">$EmpNames{$signatur}</td>\n";
  print "  <td height=\"40\">$EmpNames{$betald}</td>\n";
  print "  <td height=\"40\"> </td>\n";
  print "</tr>\n";
}
print "</table>\n";
print "</td></tr></table>\n";
}


####################################################################

sub PrintLopnummer {

my $sparalopnr = $qform{'sparalopnr'};
if ( $sparalopnr ) {
  my $lopnr = $form{'lopnr'};
  my $activity_type = $form{'activity_type'};
  goto SKIPSPARA if (!$lopnr);
  my $namn  = $form{'namn'} || ' ';
  my $beskr = $form{'beskrivning'} || ' ';
  if (my $change = $form{'change'}) {
    $SQL = qq|Update activities
              Set actid = $lopnr, name = '$namn', description = '$beskr', activity_type_id = $activity_type
              Where actid = $change|;
    &SQL_Execute($SQL);
  }
  else {
  	my $mask = 2**($lopnr-1);
    $SQL = qq|Insert Into activities (actid, name, description, maskid, activity_type_id)
              Values ($lopnr, '$namn', '$beskr', $mask, $activity_type)|;
    &SQL_Execute($SQL);
  }
}
SKIPSPARA:

my $change = $qform{'change'};
my $activity_type = $qform{'activity_type'};

print qq|<h2>Activities</h2>|;

print "<table class=table_border border=\"0\" width=\"600\" cellspacing=\"1\" cellpadding=\"2\">\n";
print "<tr class=tr_header>\n";
print "  <td width=\"80\">Activité id</td>\n";
print "  <td>Nom</td>\n";
print "  <td>Description</td>\n";
print "  <td>Type d'activit&eacute;</td>\n";
print "</tr>\n";

$SQL = qq|Select actid, name, description, id, libelle 
          From activities, activity_type 
		  Where activities.activity_type_id = activity_type.id 
          Order By actid|;
&SQL_Execute($SQL);

my(@lopch, $maxlopnr);
while ( my($lopnr, $namn, $beskr, $actId, $actType)  = &SQL_Fetch() ) {
  if ($change == $lopnr) {
    @lopch = ($lopnr, $namn, $beskr);
  }
  print "<tr>\n";
  print "  <td><a href=\"$scriptname?lopnummer=1&change=$lopnr&activity_type=$actId\">$lopnr</a></td>\n";
  print "  <td>$namn</td>\n";
  print "  <td>$beskr</td>\n";
  print "  <td>$actType</td>\n";
  print "</tr>\n";
  $maxlopnr = $lopnr;
}
$maxlopnr++;
print "</table>\n";

print "<form method=\"post\" action=\"$scriptname?lopnummer=1&sparalopnr=1\">\n";
print "<br><table border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=table_form><tr><td>\n";
print "<table border=\"0\" width=\"400\" cellspacing=\"0\" cellpadding=\"5\" >\n<tr>\n";
if (!$change) {
	print "<td colspan=\"2\"  >Nouvelle activité<input type=hidden name=lopnr value=\"$maxlopnr\"></td>\n";
} else {
	print "<td colspan=\"2\"  >Modifier activité: $change<input type=\"hidden\" name=\"lopnr\" value=\"$change\"></td>\n";
}
print "</tr><tr>\n";
print "<td>Nom<br><input type=\"text\" name=\"namn\" size=\"20\" maxlength=\"20\" class=\"ari2\" value=\"$lopch[1]\"></td>\n";
print "<td>Description<br><input type=\"text\" name=\"beskrivning\" size=\"40\" maxlength=\"40\" class=\"ari2\" value=\"$lopch[2]\"></td>\n";print "</tr><tr>\n";

$SQL = qq|Select id, libelle
          From activity_type|;
&SQL_Execute($SQL);
my %actTypes;
while ( my ($actTypeId, $actTypeLibelle)  = &SQL_Fetch() ) {
  $actTypes{$actTypeId} = $actTypeLibelle;
}
print "<td colspan=\"2\">Type d'activit&eacute; <select name=\"activity_type\">";
while ( my ($key, $value) = each(%actTypes) ) {
  my $sel;
  if ($change && $key == $activity_type) {
  	$sel = 'selected';
  }
  print "    <option $sel value=\"$key\">$value</option>\n";
}
print "</select></td>\n";
print "</tr><tr><td colspan=\"2\" align=\"right\">\n";
print "<input type=\"button\" name=\"btn_submit\" onclick=\"Javascript:"; if(!$change) { print " if(checkActivity($maxlopnr)) "; } print "submit();\" value=\"OK\" >\n";
if ($change) {
  print "<input type=\"hidden\" name=\"change\" value=\"$change\">\n";
}
print "</td></tr></table></td></tr></table>\n";
print "</form>\n";

}

#####################################################################################

sub ProjectMembers {

my $week = $qform{'week'};
my $year = $qform{'year'} || $lastyear;
my $user = $qform{'user'} || $qform{'projectmembers'};

&NamnLista;

&YearSelect("projectmembers=$user", $year);

print qq|
<h3>Affectation aux projets pour $user - $EmpNames{$user} année $year</h3>

<table width="400" border="0" cellspacing="1" cellpadding="1" class=table_border>
<tr  class=tr_header>
  <td width="150">&nbsp;Projet</td>
  <td width="150">&nbsp;Responsable</td>
  <td>&nbsp;Taux</td>
</tr>|;

$SQL = qq|Select p.projid, c.name, p.description, p.projectmanager, f.fee
          From (projects p, customers c, projectmember pm)
          	Left Outer Join fees_$year f On  f.projid=pm.projid And f.userid=pm.userid And $times[4] Between month1 And month2
          Where p.projid = f.projid And f.userid = $user And p.custid = c.custid  And (p.closed Is NULL)
          Order By f.fee Desc, p.projid|;
&SQL_Execute($SQL);
while ( my($proj, $kund, $beskr, $ledare, $arvode) = &SQL_Fetch ) {
  print "<tr>\n";
  print "  <td>&nbsp;<a href=\"$scriptname?projektdeltagare=$proj\">$proj - $kund $beskr</a></td>\n";
  print "  <td>&nbsp;$EmpNames{$ledare}</td>\n";
  print "  <td>&nbsp;$arvode</td>\n";
  print "</tr>\n";
}
print "</table>\n";


&UserPlanering($user, $year);

}

#####################################################################################

sub ProjektDeltagare($) {

my $projektdeltagare = shift;

my $week = $qform{'week'};
my $year = $qform{'year'} || $lastyear;
my $user = $qform{'user'};

$SQL = qq|Select p.projid, c.name, p.description
          From projects p, customers c
          Where p.custid = c.custid And p.closed Is NULL
          Order By p.projid|;
&SQL_Execute($SQL);

print "<form method=\"get\" action=\"$scriptname\">\n";
print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" class=table_form>\n";
print "<tr><td>Sélectionner le projet et l'année</td></tr>\n";
print "<tr><td>Projet:\n";
print "  <select name=\"projektdeltagare\" class=dropdown>\n";
while ( my($proj, $kund, $beskr) = &SQL_Fetch ) {
  if ($proj == $projektdeltagare) { print "    <option selected value=\"$proj\">$proj $kund - $beskr</option>\n"; }
  else                            { print "    <option value=\"$proj\">$proj $kund - $beskr</option>\n"; }
}
print "  </select>\n";
print "  Année: <select name=\"year\">\n";
for ($FIRST_YEAR..$lastyear+1){
	my $sel = ($_ == $year) ? 'selected' : '';
	print "<option $sel value=\"$_\">$_</option>\n";
}
print "  </select>\n";

print "  <input type=\"submit\" name=\"Submit\" value=\"  OK  \" >\n";
print "</td></tr></table>\n";
print "</form>\n";

my $change = $qform{'pmchange'};
my $delt;

if ( $qform{'pmsave'} ) {
	my $arvode = $form{'arvode'} + 0;
	my $deltagare = $form{'deltagare'};
	my $inaktiv;
	if ( $delt = $form{'change'} ) {
		if ( $inaktiv = $form{'inaktiv'} ) {
			$SQL = qq|Delete From projectmember
		          	Where userid = $delt And projid = $projektdeltagare|;
		  &SQL_Execute($SQL);
		}
	}
	else {
		$SQL = qq|Insert Into projectmember (userid, projid, visible)
            	Values ($deltagare, $projektdeltagare, 1)|;
    &SQL_Execute($SQL);
  }

  if (!$inaktiv) {
	  my $ch_hist = $form{'ch_hist'};
	  my $radio = $form{'radiobutton'};
		$deltagare = $delt if (!$deltagare);
	  my $month1 = $form{'month1'};
	  my $month2 = $form{'month2'};
	  my $omfattning = $form{'omfattning'} || 100;

	  if ($radio == 2) {
			$SQL = qq|Insert Into fees_$year (userid, projid, month1, month2, fee, type, percentage)
	        			Values ($deltagare, $projektdeltagare, $month1, $month2, $arvode, $radio, $omfattning)|;
	  }
	  elsif ($radio == 1) {
	  	$SQL = qq|Update fees_$year
	        			Set month1=$month1, month2=$month2, fee=$arvode, percentage=$omfattning
	        			Where id = $ch_hist|;
	  }
	  elsif ($radio == 3) {
	  	$SQL = qq|Delete From fees_$year
	        			Where id = $ch_hist|;
	  }
		&SQL_Execute($SQL);
	}
}

$SQL = qq|Select c.name, p.description, p.projectmanager
          From projects p, customers c
          Where (p.custid = c.custid) And (p.projid = $projektdeltagare)|;
&SQL_Execute($SQL);
my($kund, $beskr, $ledare) = &SQL_Fetch();

&NamnLista;
delete $EmpNames{666};

print qq|
<h2>Membres du projet pour $projektdeltagare - $kund $beskr année $year
  <a href="#" title="Help" onClick="helpWin('Projectmembers')"><img src="$RELPATH/help.gif" width="35" height="16" border="0" alt="Help"></a></h2>
  <p>Responsable du projet: $EmpNames{$ledare}</p>

<table border="0" cellspacing="1" cellpadding="1" class=table_border>
<tr class=tr_header>
  <td>Utilisateur</td>
  <td>Autorisation</td>
  <td>Taux</td>
</tr>|;

# returnera alla anställda med de anställda som är med i projektet överst
if ( $MYSQL ) {
	$SQL = qq|Select u.userid, name, surname, authority, fee
	          From (users u, projectmember p) Left Outer Join fees_$year f On u.userid=f.userid And $times[4] Between month1 And month2 And p.projid=f.projid
	          Where u.userid = p.userid And p.projid = $projektdeltagare And u.resigned Is NULL
	          Order By fee Desc, u.userid|;
}
else {
	$SQL = qq|SELECT e.userid, e.name, e.surname, e.authority, a.fee
	          FROM (fees_$year a) RIGHT OUTER JOIN
	               projectmember p INNER JOIN users e ON
	                  p.userid = e.userid ON e.userid = a.userid AND
	                  $times[4] BETWEEN a.month1 AND a.month2 AND a.projid = p.projid
						WHERE (p.projid = $projektdeltagare) AND (e.resigned IS NULL)
						ORDER BY a.fee DESC, e.userid|;
}
&SQL_Execute($SQL);

my($ch_arvode);
while ( my($user, $name, $surname, $title, $arvode)  = &SQL_Fetch ) {
	print "<tr>\n";
  print "  <td><a href=\"$scriptname?projektdeltagare=$projektdeltagare&year=$year&pmchange=$user\">$user - $name $surname</a></td>\n";
  print "  <td>$titles{$title}</td>\n";
  print "  <td align=\"right\">$arvode</td>\n";
  print "</tr>\n";
  if ($change && $change == $user) {
  	$ch_arvode = $arvode;
  }
#  else {
#  	$EmpNames_med{$user} = $EmpNames{$user};
#  	#delete $EmpNames{$user};
#  }
}
print "</table>\n";


my($layer, $title, $type, $radiobuttons, $discolor, $disabled, $inaktiv, $tillbaka,
   $change_hidden, $inaktiv, $historik);
if ( $change ) {
	$SQL = qq|Select month1, month2, fee, percentage, date_created, id
          	From fees_$year
          	Where userid = $change And projid = $projektdeltagare
          	Order By month1|;
	&SQL_Execute($SQL);
	my @monthsV = (0,0,0,0,0,0,0,0,0,0,0,0,0);
	my($omfattning, @historia, @array);
	while ( my($month1, $month2, $arvode, $omfattning, $datum, $id)  = &SQL_Fetch ) {
		$omfattning += 0;
		push(@historia, "<option value=\"$id\">$month1-$month2 $arvode kr $omfattning% [$datum]</option>");
		push(@array, "$month1, $month2, $arvode, $omfattning");
		for ($month1..$month2) {
			$monthsV[$_]++;
		}
	}
	$historik = join("\n",@historia);
	$tillbaka = "; document.location.href='$scriptname?projektdeltagare=$projektdeltagare'";
	$title = "Change for $EmpNames{$change}";
	print "<script>\nvar projV = new Array();\n";
	my $i;
	foreach ($i=0; $i<=$#array; $i++) {
		print "projV[$i] = new Array($array[$i]);\n";
	}
	my $monthsV_js = join(', ',@monthsV);
	print "monthsV = new Array($monthsV_js);\n";
	print "</script>\n";
	$change_hidden = "<input type=\"hidden\" name=\"change\" value=\"$change\">";
  $inaktiv = "Disabled<input type=\"checkbox\" name=\"inaktiv\" value=\"1\" onClick=\"projInaktiv()\">\n";
  $type = 'change';
  $radiobuttons = qq|<input type="radio" name="radiobutton" value="1" onClick="historik.focus()">Change period<br>
    <input type="radio" name="radiobutton" value="2" onClick="addPeriod()">Add period<br>
    <input type="radio" name="radiobutton" value="3" onClick="deletePeriod()">Remove period|;
  $layer = "layer2";
}
else {
	print "<br><input type=\"button\" name=\"Input\" value=\"Ajouter\"  onClick=\"javascript:showLayer('layer1')\">\n";
	$title = "Ajouter membre";
	$type = 'new';
	$radiobuttons = qq|<input type="hidden" name="radiobutton" value="2">|;
	$discolor = "background: lightgrey;";
	$disabled = 'disabled';
	$inaktiv = '';
	$layer = "layer1";

	$radiobuttons .= "<input type=button value='Default' onclick=\"defaultPeriod('$lastmonth')\">";
}



print qq|
<div id="$layer">
<form method="post" name="NyDeltagare" action="$scriptname?projektdeltagare=$projektdeltagare&year=$year&pmsave=1">
<table border="0" cellspacing="1" cellpadding="2" class=table_form>
<tr>
<td>
  <table border="0" cellspacing="0" cellpadding="2" class=title_bar width="100%">
    <tr><td>$title dans le projet $projektdeltagare - $kund $beskr</td>
	  <td align="right" valign="middle"><a href=# onclick="helpWin('NyDeltagare')"><img src="$RELPATH/question.gif"width="16" height="16" border="0" alt="Help"></a><a href="#" onClick="hideLayer('layer1')$tillbaka"><img src="$RELPATH/kryss.gif" width="16" height="16" border="0" alt="Stäng ned formulär"></a></td></tr>
  </table>
<table border="0" cellspacing="5" cellpadding="0">
<tr>
  <td colspan=2>Member<br>|;
    	if ($change) {
    		print "<select name=\"deltagare\" disabled >\n";
				print "<option value=\"$user\" selected>$EmpNames{$change}</option>\n";
			}
			else {
				print "<select name=\"deltagare\" onBlur=\"clear_statusbar(this.form)\" >\n";
				print "<option value=\"0\"></option>\n";
				foreach (sort {$a<=>$b} keys %EmpNames) {
					print "<option value=\"$_\">$EmpNames{$_}</option>\n";
				}
  		}

print qq|</select>
  </td>
  <td rowspan="3" width="214"> History<br>
    <select name=historik size=7 $disabled multiple style="$discolor width:250px; font-size:10px; font-family:Courier; font-weight:normal;" onChange="historik2()">
      $historik
    </select>
  </td>
</tr>
<tr>
  <td width=50>Fee<br><input type="text" name="arvode" size="6" maxlength="6" class="ari2" value="$ch_arvode" onBlur="clear_statusbar(this.form)">kr</td>
  <td width=50>Percentage<br><input type="text" name="omfattning" size="3" maxlength="3" class="ari2" value="100" onBlur="clear_statusbar(this.form)">%</td>
</tr>
<tr>
  <td colspan=2>
    Period<br><input type="text" name="month1" size="2" maxlength="2" class="ari2" value="" onFocus="checkNyDeltagare(this)">-
    <input type="text" name="month2" size="2" maxlength="2" class="ari2" value="" onblur="clear_statusbar(this.form)">
    <input type="hidden" name="type" value="$type">
    <input type="hidden" name="ch_hist" value="">
  </td>
</tr>
</table>
<table border="0" cellspacing="5" cellpadding="0"  width="100%">
<tr>
  <td width="150">
    $radiobuttons
  </td>
  <td width="10">
    $inaktiv
  </td>
  <td valign=bottom align=right>
  <input type="submit" name="submit" value="OK" onClick="submitData(event, validera_NyDeltagare())">
  </td>
</tr>
<tr>
  <td colspan="3"><div id=statusbar2>&nbsp;</div></td>
</tr>
</table>
</td></tr></table>
$change_hidden
</form>
</div>
|;

if (!$change){
	&ProjektPlanering($projektdeltagare, $year);
}

}


#####################################################################################
sub Almanacka {

my $year = $qform{'year'} || $lastyear;
my $first_monday = $dayOfJan1[$year - 2000] - 3 ;
$months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår
#$months[2] = 29 if ( $year % 4 == 0 );  # skottår

my $arbetstid;

if ( $qform{ 'save' } ) {
  my $vecka = $form{ 'week' };
  my $arbetstid = $form{ 'arbetstid' };
  my $kommentar = $form{ 'kommentar' };
  my $reddays;
  for (1..5) {
    if ( $form{'check' . $_} ) {
      $reddays .= $_ . ',';
    }
  }
  chop $reddays;
  $SQL = qq|Update workhours_$year
            Set hours = $arbetstid, comment = '$kommentar', freedays = '$reddays'
            Where week = $vecka|;
  &SQL_Execute($SQL);
}

#  $first = 1; # första måndagen på året
  my $kommentar = "";

  $SQL = qq|Select week, freedays, hours, comment
            From workhours_$year
            order by week ASC|;
  &SQL_Execute($SQL);

  my(@veckor, %year_checked);
  while ( my ($week, $red, $tid, $kom) = &SQL_Fetch() ) {
     @veckor[$week] = [$red, $tid, $kom];
		$_ = $red;
		next if m/^ +$/;
     my @checkV = split /,/, $red;
     foreach (@checkV) {
        $year_checked{ $_ + ($week-1)*7 + $first_monday-1 } = 1;
     }
  }

  &YearSelect('almanacka=1',$year);
  my @day_jus   = (0,2,4,6,2,3,0); #2000 ... 2006 2007

  my $day_count = $day_jus[$year - 2001];
  my $week = 1;

  print "<h2>Weekly work hours $year\n";
  print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('Kalender')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h2>\n";

  print "<table class=table_border border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
  print "<tr class=tr_header>\n";
  print "   <td style=\"background-color: #999999\" width=\"70\"></td>\n";
  for (1..31) {
      print "   <td style=\"background-color: #999999\" width=\"16\"  align=\"center\">$_</td>\n";
  }

# Suite à bug 2007 (1er janvier = lundi).
# le deuxième my() fait RAZ des variables précédemment déclarées
#  my($i, $bgcolor, $day_count, $week, $j, $align, $lastmonday, $titletag, $checkstr, $week2);
  my($i, $bgcolor, $j, $align, $lastmonday, $titletag, $checkstr, $week2);
  print "</tr>\n";
    for $i(1..12) {
      print "<tr>\n";
      print "   <td style=\"background-color: lightgrey\">$Months_W[$i]</td>\n";
      for $j(1..31) {
        if ($j > $months[$i]) {
          $bgcolor= "lightgrey";
        }
        else {
          $day_count++;
          if (($day_count - $first_monday + 1) % 7 == 0 || ($day_count - $first_monday + 2) % 7 == 0) {
            $bgcolor= "yellow";
          }
          else {
            $bgcolor= "ffffff";
          }
          if ( ($day_count - $first_monday ) % 7 == 0 || ($i*$j == 1) ) {
            $week++ if ( $i*$j > 1);            # visa 1 för första veckan (även om den börjar på t ex onsdag)
            # if ($week > 52) { $week = 1; }
            $lastmonday = $j;
            $arbetstid = $veckor[$week][1] + 0;
            $kommentar = $veckor[$week][2];
            $titletag = $kommentar;
            $kommentar =~ s![\n]!\\n!ig; # substituera \n mot '\n'
            $checkstr = $veckor[$week][0];
            $week2 = "<a href=\"javascript:fyllRuta($week,$arbetstid,'$kommentar','$checkstr')\" title=\"$titletag\">$week</a>";
            $align = ' align="center" ';
          }
          else {
            my $week2 = '&nbsp;';
            my $align = '';
          }
          if ( $year_checked{ $day_count } ) {
            $bgcolor= 'red';
          }
        }
        print "   <td style=\"background-color: $bgcolor\" $align>$week2</td>\n";
        $week2 = '&nbsp;';
        $align = '';
      }
      print "</tr>\n";
    }
  print "</table>\n";


print "<br>\n";
print "<table width=\"600\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" class=table_border>\n";
print "  <TR class=tr_header>\n";
print "    <TD width=\"50\">Semaine</TD>\n";
print "    <TD width=\"50\">Heures travaillées</TD>\n";
print "    <TD>Commentaire</TD>\n";

#while ( my ($week, $arbetstid, $kommentar, $red) = &SQL_Fetch() ) {

my $i = 0;
foreach (1..52) {
	$week =   $_;

	my ($red, $tid, $kom) = @{ $veckor[$_] };

  if ($red) {
    $week = '<font color="red">'.$week.'</font>';
  }

  print "  <TR>\n";
  print "    <TD>$week</TD>\n";
  print "    <TD>$tid</TD>\n";
  print "    <TD>$kom</TD>\n";
  print "  </TR>\n";
}
print "</table>\n";



#print "  </TR>\n";
print "</table>\n";

#print "  <IFRAME NAME=ifrm src=\"$scriptname?almanacka2=$year\" frameborder=\"no\" align=\"top\" STYLE=\"HEIGHT: 200; WIDTH: 600;margin-top:0px; border-width:1; border-style:solid;\"></IFRAME><br>\n";


print qq|
<div id="layer1" >
<form name="ruta" method="post" action="$scriptname?almanacka=1&save=1&year=$year">
<table border="0" cellspacing="1" cellpadding="1" class="table_form" >
<tr>
  <td>
  		<table border="0" cellspacing="0" cellpadding="1" class="title_bar" width="100%">
  		<tr>
      	<td>Modifier heures de travail, commentaires</td>
      	<td align="right" valign="middle"><a href=# onclick="helpWin('Arbetstid')"><img src="$RELPATH/question.gif"width="16" height="16" border="0" alt="Help"></a><a href="#" onClick="hideLayer('layer1'); showLayer('layer2')"><img src="$RELPATH/kryss.gif" width="16" height="16" border="0" alt="St&auml;ng ned formul&auml;r"></a></td>
      </tr>
      </table>
  </td>
</tr>
<tr>
  <td>
      <table border="0" cellspacing="5" cellpadding="0">
      <tr>
        <td>Week:</td>
        <td><input type="text" name="week" size="3" value="13" style="background-color:lightgrey; border-width:0; font-weight:bold; font-size:14px"></td>
        <td>Hours:<br><input type="text" name="arbetstid" size="4" maxlength="3"></td>
        <td>
          <font color="#FF0000">Holidays:</font><br>
          <table border="0" cellspacing="1" cellpadding="2">
            <tr>
              <td bgcolor="#999999" align="center">Lun</td>
              <td bgcolor="#999999" align="center">Mar</td>
              <td bgcolor="#999999" align="center">Mer</td>
              <td bgcolor="#999999" align="center">Jeu</td>
              <td bgcolor="#999999" align="center">Ven</td>
            </tr>
            <tr>
              <td align="center"><input type="checkbox" name="check1" value="1"></td>
              <td align="center"><input type="checkbox" name="check2" value="1"></td>
              <td align="center"><input type="checkbox" name="check3" value="1"></td>
              <td align="center"><input type="checkbox" name="check4" value="1"></td>
              <td align="center"><input type="checkbox" name="check5" value="1"></td>
            </tr>
          </table>
			</td>
			</tr>
			</table>
		</td>
	</tr>
<tr>
	<td>Thème:<br><textarea name="kommentar" rows="5" cols="40"></textarea></td>
</tr>
<tr>
	<td align="right"><input type="submit" name="OK" value=" OK "></td>
</tr>
</table>
</form>
</div>
|;

}

#####################################################################################
sub Almanacka2($) {

my $year = shift;
$SQL = qq|Select week, arbetstid, kommentar, red
          From arbetstid_$year
          Order By week|;
&SQL_Execute($SQL);

$_ = <<EOP;

<HTML>
  <HEAD></HEAD>

<style type="text/css">
<!--
   a:link    {  color: blue; text-decoration: none; }
   a:visited {  color: blue; text-decoration: none; }
   a:hover   {  color:red;    text-decoration: underline; }
   td        {  font-size: 11px; font-family: arial; font-weight: normal; color: #000000;}
-->
</style>

<body bgcolor="#FFFF99" leftmargin="0" topmargin="0" marginwidth="0">

EOP
print;

print "<table width=\"584\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\" class=table_border>\n";
while ( my ($week, $arbetstid, $kommentar, $red) = &SQL_Fetch() ) {
  if ($red) {
    $week = '<font color="red">'.$week.'</font>';
  }
  $kommentar =~ s![\n]!<br>!ig; # substituera \n mot <br>
  print "  <TR>\n";
  print "    <TD width=\"50\">$week</TD>\n";
  print "    <TD width=\"50\">$arbetstid</TD>\n";
  print "    <TD>$kommentar</TD>\n";
  print "  </TR>\n";
}
print "</table>\n";

}

#####################################################################################
sub Planering {

for (2..12) {
	$months[$_] += $months[$_ - 1];
}

my $month = $qform{'month'}; # || $times[4];
my $year =  $qform{'year'}  || $lastyear;

my $kategori = $qform{'radio_val'} || 1;
my %val_checked = ($kategori => 'checked');

my($radio, $week, $week1, $week2, @checked);
if ( !($radio = $qform{'radio_period'}) ) {
	$month ||= $times[4];
	@checked = ('checked','');
	$radio = 1;
}
else {
	if ($radio == 2) {
		$week1 = $qform{'week1'}; #|| 1;
		$week2 = $qform{'week2'}; #|| $lastweek;
		@checked = ('', 'checked');
    my $i;
		for ($i=1; $months[$i] < $week1*7; $i++) {}

		$month = $i;                
	}
	else {
		@checked = ('checked', '');
	}
}

my($day1, $day2);
if ( $radio == 1 ) {
  ($week1, $week2, $day1, $day2) = &Month2Week($month,$year);
}

my $dagar = ($week2 - $week1 + 1)*5 - ($day1-1) - (5-$day2);

$SQL = qq|Select week, freedays
          From workhours_$year
          Where week Between $week1 And $week2|;
&SQL_Execute($SQL);
while ( my ($vecka, $red) = &SQL_Fetch ) {
	my @veckan = (0, 0,0,0,0,0);
	my @reds = split( /,/ , $red);
	foreach (@reds){
		$veckan[$_] = 1;
	}
	if ($vecka == $week1) {
		for (1..$day1) { $veckan[$_] = 0; }
	}
	if ($vecka == $week2) {
		for ($day2..7) { $veckan[$_] = 0; }
	}
	foreach (@veckan){
		if ($_) { $dagar--; }
	}
}
my $timmar = $dagar * $hours_a_day;

print qq|<form method="get" action="$scriptname">
  <input type="hidden" name="prognos" value="1">
<table border="0" cellspacing="0" cellpadding="3" class=table_form>
	<tr><td colspan="2">Observer les recettes prévisionnelles en se basant sur la période :</td></tr>
	<tr>
		<td colspan="2">
	  	<input type="radio" name="radio_val" value="1" $val_checked{1}>Par projet
			<input type="radio" name="radio_val" value="2" $val_checked{2}>Par utilisateur
		</td>
	</tr>
	<tr>
            <td>
		<input type="radio" name="radio_period" value="1" $checked[0]>Mois
		<select name="month">|;

for (0..12) {
  my $sel =  ($_ == $month) ? 'selected' : '';
  print "  <option $sel value=\"$_\">$Months_W[$_]</option>\n";
}
print "</select>\n</td>\n";
print qq|<td><input type="radio" name="radio_period" value="2" $checked[1]>Hebdo|;
&PeriodSelect($week1,$week2,$year);
print "  <input type=\"submit\" name=\"Submit\" value=\"OK\" >\n";
print "</b></td></tr></table>\n";
print "</form>\n";

# plocka ut beskrivning och övertidfaktorer. Join av perf skäl
$SQL = qq|Select projid, c.name, p.description, overtime1, overtime2
          From projects p, customers c
          Where p.custid = c.custid|;
&SQL_Execute($SQL);
my(%projekt_beskr, %tidtyp);
while ( my($proj, $kund, $beskr, $ot1, $ot2) = &SQL_Fetch ) {
	my $besk = "$kund - $beskr";
	$beskr =~ s/ /&nbsp;/g;
	$projekt_beskr{$proj} = $besk;
	$tidtyp{$proj} = [1, $ot1, $ot2, 0, 0, 0];
}

# vid vecko-rapport används arvodet för månaden som week1 ligger i
$SQL = qq|Select userid, projid, percentage, fee
          From fees_$year
          Where $month Between month1 And month2
          Order by userid, projid|;
&SQL_Execute($SQL);
my(%f_proj_tim, %f_proj_sum, $f_totsum, %arvode_h, %proj_h);
while ( my($userid, $projekt, $omfattn, $arvode) = &SQL_Fetch ) {
	my $tid = $omfattn*$timmar/100;
	$f_proj_tim{$projekt} += $tid;
	$f_proj_sum{$projekt} += $tid*$arvode;
	$f_totsum += $tid*$arvode;

	$arvode_h{ "$userid-$projekt" } = $arvode;
	$proj_h{$projekt} = 2;
}

$SQL = qq|Select week, userid, projid, timecode, Sum(monday) d1, Sum(tuesday) d2, Sum(wednesday) d3,
                                     Sum(thursday) d4, Sum(friday) d5, Sum(saturday) d6, Sum(sunday) d7
          From reports_$year
          Where week Between $week1 And $week2
          Group By week, userid, projid, timecode|;
&SQL_Execute($SQL);

my(%projekt_sum, %projekt_tim, $totsum);
while ( my($week, $userid, $projekt, $tidkod, @vsum) = &SQL_Fetch ) {
	my $summa = 0;
	if ($radio == 1) {   # vid månadsrapport använd exakta dagar
		if ($week == $week1) {
	  	for (0..$day1-1) { $vsum[$_] = 0; }
	  }
		if ($week == $week2) {
	  	for ($day2-1..6) { $vsum[$_] = 0; }
	  }
	}
	for (0..6) {
		$summa += $vsum[$_];
	}

	my $arvode = $arvode_h{ "$userid-$projekt" } * $tidtyp{$projekt}->[$tidkod];
  my $sum = $arvode*$summa;
  $projekt_sum{$projekt} += $sum;
  $projekt_tim{$projekt} += $summa;
  $totsum += $sum;

  $proj_h{$projekt} = 1;
}

$f_totsum = &Format(int $f_totsum);
my $summa = &Format($totsum);

my $period = ($radio == 1) ? $Months_W[$month] : "Semaines $week1 - $week2,";

print qq|<h2>Prévisions
  <a href="#" title="Help" onClick="helpWin('Prognos')"><img src="$RELPATH/help.gif" width="35" height="16" border="0" alt="Help"></a></h2>
  <p>Revenus de référence: <font color=red>$summa</font> \€<br>
     Revenus prévisionnels: <font color=red>$f_totsum</font> \€<br>
     $period $timmar heures ouvrées</p>|;
print "<p><a class=\"a3\" href=\"$scriptname?semester=1&year=$year\" target=_new title=\"Se de anställdas planerade semesterdagar\">Hebdomadaire</a><br>\n";
print "   <a class=\"a3\" href=\"$scriptname?deltagareyear=$year\" target=_new title=\"Se projektmedverkan under året\">Par membre & Projet</a></p>\n";

print qq|

<table border=0 cellspacing=1 cellpadding=1 class=table_border>
<tr class=tr_header>
  <td width=80>&nbsp;</td>
  <td colspan=2 align=center>Actuel</td>
  <td colspan=2 align=center>Prévisionnnel</td>
<tr>
<tr class=tr_header>
	<td width=80>&nbsp;Projet</td>
  <td width=80 align=center>Somme&nbsp;[\€]&nbsp;</td>
  <td width=80 align=center>Horaire&nbsp;</td>
  <td width=80 align=center>Somme&nbsp;[\€]&nbsp;</td>
  <td width=80 align=center>Horaire&nbsp;</td>
<tr>|;

delete $proj_h{400}; # ta bort semester

my $proj;
foreach $proj(sort { $proj_h{$a}*1000+$a <=> $proj_h{$b}*1000+$b } keys %proj_h) {
  my $sum = $projekt_sum{$proj};
  $sum = &Format($sum);
  my $tim = $projekt_tim{$proj};
  my $f_sum = &Format(int $f_proj_sum{$proj});
  my $f_tim = int $f_proj_tim{$proj};
  if (!$f_tim || !$sum) {
  	$f_tim = "<font color=red>$f_tim</font>";
  	$f_sum = "<font color=red>$f_sum</font>";
  	$tim = "<font color=red>$tim</font>";
  	$sum = "<font color=red>$sum</font>";
  }
  print "<tr>\n";
  print "  <td>&nbsp;<a href=\"$scriptname?faktura=1&projnr=$proj&month=0&year=$year\">$proj&nbsp;$projekt_beskr{$proj}</a></td>\n";
  print "  <td align=\"right\">$sum&nbsp;</td>\n";
  print "  <td align=\"right\">$tim&nbsp;</td>\n";
  print "  <td align=\"right\">$f_sum&nbsp;</td>\n";
  print "  <td align=\"right\">$f_tim&nbsp;</td>\n";
  print "</tr>\n";
}
print "</table>\n";


}

#####################################################################################
sub Format($) {
  my $totsum = shift;
  my $totsum2 = int($totsum);
  #my $totsum2 = $totsum;
  my $totsum3 = $totsum - $totsum2;
  if ($totsum3 > 0) {
    $totsum3= substr $totsum3 , 2;
  }
  my @pad = ('','  ',' ');
  my $totsum2 = $pad[length($totsum2) % 3].$totsum2;   # padda strängen så den är en multipel av tre
  $totsum2 =~ s/[0-9 ]{3}/$& /g;                  # lägg en blank efter teckenserier om 3
  $totsum2 =~ s/^[ ]+//;                          # ta bort inledande blanka (padding)
  chop $totsum2;
  return ($totsum2.".".$totsum3);
}

#####################################################################################
sub Month2Week($$) {
  my ($m, $year) = @_;
  my $first_jeudi = $dayOfJan1[$year - 2000];
  my $first_lundi = $first_jeudi - 3;

  my @months = (0,31,28+0,31,30,31,30,31,31,30,31,30,31);
  $months[2] = 29 if ( !(($year - 2000) % 4) );  # skottår

  my $dagar = 0;
  my $i;
  for ($i=1; $i<$m; $i++) {
    $dagar += $months[$i];
  }

# 1ère semaine (w1), 1er jour (d1)
  my $w1 = int(($dagar - ($first_lundi - 1)) / 7) + 1;
  my $d1 = $dagar  - (($w1 - 1) * 7 + $first_lundi) + 2;
  if ($d1 == 0) {
    $d1 = 7;
  }
  if ($w1 == 1) {
    $d1 = 1;
  }
  
# dernière semaine (w2), dernier jour (d2)
  $dagar += $months[$m] - 1;
  my $w2 = int(($dagar - $first_jeudi + 4)/7) + 1;
  my $d2 = $dagar - (($w2 - 1) * 7 + $first_lundi) + 2;

  if ($d2 == 0) {
    $d2 = 7;
    $w2--;
  }
  return ($w1, $w2, $d1 , $d2);
}

#####################################################################################
sub Week2Month($$) {
  my ($week, $year) = @_;
  my $first_monday = $dayOfJan1[$year - 2000] - 3;
  my $days = $week*7 + $first_monday;
  my $i = 0;
  my $d = 0;
  do {
    $d += $months[++$i];
  } while ($d < $days);
  return $i;
}

#####################################################################################
sub Tidbank($) {

my $showtidbank = shift;

my($year, $maxweek, $maxyear, $submit_button, $tidbank);

if ( my $userid = $form{'sparatidbank'} ) {  # uppdatera tidbank
  my $trans_nytid = $form{'trans_nytid'};
  my $transtid    = $form{'transtid'};
  my $transweek   = $form{'transweek'};
  my $transtyp    = $form{'transtyp'};
  my $kommentar   = $form{'kommentar'};
  $year           = $form{'year'};

  $SQL = qq|Insert Into tidbank (userid, year, week, original, changed, uttag, kommentar)
            Values ($userid, $year, $transweek, $transtid, $trans_nytid, $transtyp, '$kommentar')|;
  if ( &SQL_Execute($SQL) ) {
    $SQL = qq|Update weeks
              Set tidbank = $trans_nytid
              Where (userid = $userid) And (week = $transweek) AND year = $lastyear|;
    &SQL_Execute($SQL);
  }
}

&NamnLista($showtidbank);


$SQL = qq|Select Max(year*100+week)
          From weeks
          Where userid = $showtidbank|;
&SQL_Execute($SQL);
$maxweek = &SQL_Fetch;

$maxyear = int($maxweek/100);
$maxweek = $maxweek % 100;


if ( $maxweek ) {
  $SQL = qq|Select tidbank
            From weeks
            Where (userid = $showtidbank) And (week = $maxweek) AND year = $maxyear|;
  &SQL_Execute($SQL);
  $tidbank = &SQL_Fetch();
  $submit_button = qq|<input type="submit" name="submit" value="OK" onClick="submitData(event, validera_tidbank())">|;
}
else {
	$SQL = qq|Select begindate
          	From users
          	Where userid = $showtidbank|;
	&SQL_Execute($SQL);
	my $begindate = &SQL_Fetch;
	$begindate = substr($begindate, 0, 4);

	$submit_button = "Personen har inte rapporterat i systemet ännu, ändra tidbank från 'Ändra anställd'";
}
$tidbank += 0;

my %tidtyp = (1=>'Kompledighet', 2=>'Lön');
$SQL = qq|Select year, week, original, changed, withdrawal, comment
          From timebank
          Where userid = $showtidbank
          Order by year Desc, week Desc|;
&SQL_Execute($SQL);
my(@historik, $tyear, $tweek);
while ( my ($year, $week, $orig, $changed, $uttag, $kommentar) = &SQL_Fetch() ) {
  my $line = "<option>$year/$week [$orig»$changed] $tidtyp{$uttag} \"$kommentar\"</option>\n";
  push(@historik, $line);
  $tyear = $year;
  $tweek = $week;
}

if ( $tyear==$maxyear && $tweek==$maxweek ) {
  $submit_button = "The timebank has already been adjusted for week $tweek !";
}

print qq|
<div id="layer2">

<form method="post" name="tidbank" action="$scriptname?showtidbank=$showtidbank">
<br>
<table border="0" cellspacing="1" cellpadding="2" class=table_form>
<tr>
<td>
	<table class=title_bar width=100% cellpadding=2 cellspacing=0>
  <tr>
  <td>Adjust timebank for $EmpNames{$showtidbank} [$showtidbank]</td>
  <td align="right" valign="middle"><a href=# onclick="helpWin('Timebank')"><img src="$RELPATH/question.gif"width="16" height="16" border="0" alt="Help"></a><a href="#" onClick="javascript:hideLayer('layer1')"><img src="$RELPATH/kryss.gif" width="16" height="16" border="0" alt="Stäng ned formulär"></a></td></tr>
	</table>
</td>
</tr>
<tr>
<td>
<table border="0" cellspacing="5" cellpadding="0">
<tr>
  <td colspan="2" class="td_form">
    History:<br>
    <select name="historik" size="5" style="width:350px; height: 100px; font-size:10px; font-family:Courier; font-weight:normal;">
      @historik
    </select>
  </td>
</tr>
<tr>
  <td>Current timebank:<br><font face="courier"><font color="red">$tidbank</font> [v$maxweek-$maxyear]</font></td>
  <td>New timebank:<br><input type="text" name="trans_nytid" size="8" maxlength="8" value="" maxlength="2" class="ari2"></td>
</tr>
<tr>
  <td><input type="radio" name="radio" value="1">Comp free time</td>
  <td><input type="radio" name="radio" value="2">Salary</td>
</tr>
<tr>
  <td colspan="2" class="td_form">Theme:<br><textarea name="kommentar" rows="4" style="width:350px"></textarea></td>
</tr>
<tr>
  <td colspan="2" align="right">
    <input type="hidden" name="sparatidbank" value="$showtidbank">
    <input type="hidden" name="transweek" value="$maxweek">

    <input type="hidden" name="transtid" value="$tidbank">
    <input type="hidden" name="transtyp" value="0">
    <input type="hidden" name="year" value="$maxyear">
    $submit_button
  </td>
</tr>
<tr>
  <td colspan="2"><div id=statusbar2>&nbsp;</div></td>
</tr>
</table>

</td></tr></table>

</form>
</div>
|;

}

#####################################################################################
sub Semester {

$| = 1;

my $year = $qform{'year'};

$SQL = qq|Select userid, name, surname
          From users
          Where resigned Is NULL
          Order By userid|;
&SQL_Execute($SQL);
my(@user_lista, %namn_lista);
while ( my ($userid, $name, $surname) = &SQL_Fetch() ) {
  push(@user_lista,$userid);
	$namn_lista{$userid} = "$name&nbsp;$surname";
}

my $SQL = qq|Select Distinct week, userid
             From reports_$year
             Where actid = 501|;
&SQL_Execute($SQL);
my %vecko_rader;
while ( my($week, $userid) = &SQL_Fetch() ) {
  $vecko_rader{$userid}{$week} = 1;
}

print "<br><h3>Hedomadaire $lastyear</h3>";

print "<table border=\"0\" width=\"1000\" cellspacing=\"1\" cellpadding=\"0\" class=table_border>\n";

print "<tr class=tr_header>\n<td width=\"150\">&nbsp;</td>";
my @bgcol = ('blue','green');
my $days = -$first_monday;
my $totcol;
for (1..12) {
  $days += $months[$_];
  my $x = int($days/7) - $totcol;
  $x++ if ($days % 7);
  $totcol += $x;
  my $bgcolor = $bgcol[$_ % 2];
  print "<td align=\"center\" colspan=\"$x\" style=\"background-color: $bgcolor\"><font size=1>$Months_W[$_]</font></td>";
}
print "\n</tr>\n";

print "<tr class=tr_bold>\n<td width=\"150\">&nbsp;</td>";
for (1..52) {
  print "<td align=\"center\" width=\"15\">$_</td>";
}
print "\n</tr>\n";

my @bgcolor = ('white', 'red');
foreach (@user_lista) {
  my $userid = $_;
  print "<tr>\n<td class=td_form>&nbsp;$userid&nbsp;$namn_lista{$_}$EmpNames{$userid}</td>";
  for (1..52) {
    print "<td style=\"background-color: ", $bgcolor[ $vecko_rader{$userid}{$_} ], "\">&nbsp;</td>";
  }
  print "\n</tr>\n";
}
print "</table>\n";

}



#####################################################################################
sub SQL_Execute($) {
  my @params = @_;
  my $kommando = ($params[0]);
  my $SQL  = qq| @params |;

  if ( $sth = $dbh->prepare($SQL) ) {
    if ( my $rc = $sth->execute ) {
      return 1;
    }
    else {
      print "<div class=sql><pre width=60>Can't execute statement: $DBI::errstr\n\n";
      print "$kommando\n";
      print "</div>\n";
      return 0;
    }
  }
  else {
  	&PrintHeader if (!$PRINTHEADER);
    print "\n\nCan't prepare statement: $DBI::errstr";
    return 0;
  }
}

#####################################################################################
sub SQL_Fetch($) {
  return ( $sth->fetchrow_array );
}

#####################################################################################
sub SQL_Close($) {
  #die $sth->errstr if $sth->err;
  $dbh->disconnect;
}

#####################################################################################
sub SQL_Connect {

	if ($MYSQL) {
		$dbh = DBI->connect("DBI:mysql:$DSN:$host",$opt_user,$opt_password,{ PrintError => 0});
	} else {
  	$dbh = DBI->connect("dbi:ODBC:$DSN", $opt_user,$opt_password, { PrintError => 0});
  }

  if (!$dbh) {
  	&PrintHeader;
  	print "<b>Kan ej ansluta till databas: $DBI::errstr</b>\n";
  	die "Can't connect: $DBI::errstr\n";
  }
}

#####################################################################################
sub setCookie {
  # end a set-cookie header with the word secure and the cookie will only
  # be sent through secure connections
  my($name, $value, $expiration, $path, $domain, $secure) = @_;

  print "Set-Cookie: ";
  print ($name, "=", $value , "\n" ); #, "; expires=", $expiration,
    #"; path=", $path, "; domain=", $domain, "; ", $secure, "\n");
}

#####################################################################################
sub getCookies {
  # cookies are seperated by a semicolon and a space, this will split
  # them and return a hash of cookies

  my $http_cookie = $ENV{'HTTP_COOKIE'};
  my(@rawCookies) = split (/; /,$http_cookie);
  my(%cookies);

  foreach (@rawCookies){
  	my($key, $val) = split /=/;
  	$cookies{$key} ||= $val;
  }

  return %cookies;
}

#####################################################################################
sub Valid {


  my $user   = $cookies{ 'user' };

  return 0 if (!$user);

  my $role   = $cookies{ 'role' };
  my $tid    = $cookies{ 'issued' };

  if ( (my $diff = $abstime - $tid) > $TIME_LIMIT){
    $validerings_fel = "Automatiskt utloggad ($diff min)";
    return 0;
  }

  my $digest = $cookies{ 'digest' };
  my $check  =  md5_base64( "$user$role$tid$salt" );

  return ($digest eq $check);

}

#####################################################################################
sub NamnLista($$) {

my($limit_user, $inloggade) = @_;
if ($limit_user) {
  $limit_user = "And userid = $limit_user";
}

$SQL = qq|Select userid, name, surname, authority, loggedin
         From users
         Where resigned Is NULL
         $limit_user
		 And surname not like '#%'
         Order By userid|;
&SQL_Execute($SQL);

@emps = ('');
@ProjLeaders = (0);
%EmpNames = ();
# Chargement des utilisateurs courants
while ( my ($idnr, $name, $surname, $title, $inlogg) = &SQL_Fetch() ) {
  push (@emps,$idnr);
  
  $surname =~s/#$//g;
  $EmpNames{$idnr} = $name.'&nbsp;'.$surname;
  if ($title > 0) { push (@ProjLeaders,$idnr); }
  if ($inloggade) {
    $inloggad{$idnr} = $inlogg;
  }
}
$SQL = qq|Select userid, name, surname, authority, loggedin
         From users
         Where resigned Is NULL
         $limit_user
		 And surname like '#%'
         Order By userid|;
&SQL_Execute($SQL);
# Chargement des utilisateurs moins courants
while ( my ($idnr, $name, $surname, $title, $inlogg) = &SQL_Fetch() ) {
  push (@emps,$idnr);
  
  $surname =~s/#$//g;
  $EmpNames{$idnr} = $name.'&nbsp;'.$surname;
  if ($title > 0) { push (@ProjLeaders,$idnr); }
  if ($inloggade) {
    $inloggad{$idnr} = $inlogg;
  }
}

$EmpNames{666} = '-Superuser-';

}

#####################################################################################
sub SuperUser {

&NamnLista;


my $year;

$SQL = qq|Select userid, Max(week)
          From timebank
          Where year = $lastyear
          Group By userid|;
&SQL_Execute($SQL);

my %tid_week;
while ( my($user, $week) = &SQL_Fetch() ) {
 $tid_week{$user} = $week;
}

my %tagit_bort;

if ( $qform{'change'} ) {
	my $user = $form{'id'};

	$SQL = qq|Select projid
	          From projectmember
	          Where userid = $user|;
	&SQL_Execute($SQL);
	my $projektlista;
	while (my $proj = &SQL_Fetch) {
		$projektlista .= "$proj,";
	}
	chop $projektlista;
	$SQL = qq|Select projid, Max(week2)
	          From invoices_$year
	          Where projid In ($projektlista)
	          Group By projid|;
	&SQL_Execute($SQL);
	my($maxweek2, $maxproj);
	while ( my($proj, $week2) = &SQL_Fetch ) {
		if ($week2 > $maxweek2) {
			$maxweek2 = $week2;
			$maxproj = $proj;
		}
	}
	print "<p>Senaste semaine $maxweek2 för projekt <a href=\"$scriptname?faktura=1&projnr=$maxproj&week2=$maxweek2&year=$lastyear\">$maxproj</a> där $EmpNames{$user} var inblandad.</p>";

	$SQL = qq|Select Max(week)
          	From weeks
            Where userid = $user AND year = $lastyear|;
	&SQL_Execute($SQL);
	my $maxweek = &SQL_Fetch;
 	my $tid_just = $tid_week{$user} + 0;
	if ($maxweek > $tid_just) {
		$SQL = qq|Delete From weeks
	            Where userid = $user And week >= $maxweek And week > $tid_just AND year = $lastyear|;
		if ( &SQL_Execute($SQL) ) {
			$tagit_bort{$user} = "<font color=red><b>[$maxweek]</b></font>";
			print "<p><font color=red>Tagit bort stämpel för semaine $maxweek för $EmpNames{$user}</font></p>";
		}
	}
	else {
		print "<p><font color=red>Det går inte a ta bort stämpel $maxweek för $EmpNames{$user} eftersom tidbank justerats flr den semaines.</font></p>";
	}

}

$SQL = qq|Select userid, Max(week)
          From weeks
          WHERE year = $lastyear
          Group By userid
          Order By userid|;
&SQL_Execute($SQL);

print "<table><tr><td>\n";

print qq|<p>Stämpeln "Färdigrapporterad" för senaste semaine (ned till semaine där<br>
            tidbank justerats) kan tas bort av superuser.</p>
         <table border=0 cellspacing=1 cellpadding=1 class=table_border width=300>
         <tr class=tr_header>
           <td>user</td>
           <td>week</td>
           <td>tidbank justerad v</td>
         </tr>|;
while ( my ($user, $week) = &SQL_Fetch() ) {
	if ($tid_week{$user} >= $week) {
		$week = "<b><font color=red>$week</font></b>";
	}
  print qq|<tr>
            <td class=td_vanlig>$user $EmpNames{$user}</td>
            <td class=td_vanlig>$week $tagit_bort{$user}</td>
            <td class=td_vanlig>$tid_week{$user}</td>
          </tr>|;
}
print qq|</table>

         <form name=superuser method="post" action="$scriptname?superuser=1&change=1">
           User: <input type=text name=id size=4 maxlength=3>
           <input type=submit value="Ta bort stämpel">
         </form>|;

print "</td><td valign=top>\n";


print qq|<p>Stämpeln "Färdigrapporterad" för senaste semaine (ned till semaine där<br>
            tidbank justerats) kan tas bort av superuser.</p>
         <table border=0 cellspacing=1 cellpadding=1 class=table_border width=300>
         <tr class=tr_header>
           <td>projet</td>
           <td>period</td>
           <td>fakt justerad v</td>
         </tr>|;

$SQL = qq|Select projid, Max(month), Min(month)
          From invoices_$lastyear
          Where date_posted IS NULL
          Group By projid
          Order By projid|;
&SQL_Execute($SQL);

while ( my ($proj, $month, $month2) = &SQL_Fetch() ) {

  print qq|<tr>
            <td class=td_vanlig>$proj</td>
            <td class=td_vanlig>$Months_W[$month]</td>
            <td class=td_vanlig>$Months_W[$month2]</td>
          </tr>|;
}

print qq|</table>|;

print "</td></tr></table>\n";


}

#####################################################################################
sub min2Date($) {

  my $c;
  my @months2 = (0,31,28+0,31,30,31,30,31,31,30,31,30,31);
  if (!$c++){
#    my @months2 = (0,31,28+0,31,30,31,30,31,31,30,31,30,31);
    for (1..12) {
      $months2[$_] += $months2[$_ - 1];
    }
  }

  my $tid = shift;

  my($dagar, $tim, $min, $mm, $dd, $i);

  $dagar = int $tid/(24*60);

  $min = $tid - $dagar*24*60;
  $tim = int($min/60);
  $min =$min % 60;

  for ($i=int($dagar/28); $months2[$i]<$dagar; $i++) {}

  $mm = $i;
  $dd = $dagar - $months2[$mm-1] + 1;

  $tim = "0$tim" if ($tim < 10);
  $min = "0$min" if ($min < 10);

  return "$dd/$mm [$tim:$min]";
}

#####################################################################################
sub printUserweek($) {

	my ($user_role, %rapvecka, %tidbank, $arbetstid_weeks, %fakvecka, %lopnr_beskr, %proj_beskr,
	@red_weeks, @arbetstid_weeks, $checkbolin);

	my @rows = @{$_};

	my $week = 33;




}

#####################################################################################
sub UserTidKort($$$$) {

my($empo, $week1, $week2, $year) = @_;

my $user_role = 0;
if (!$week1 && !$week2) {
	$week1 = 1;
	$week2= 52;
	$year = $lastyear;
	$user_role = 1;
	&NamnLista;
}

#$year = $qform{'year'} || $lastyear;
$year ||= $lastyear;

if ($AccessRight) {
	print "<input type=button value=\"Mensuel\" onClick=\"javascript:document.location='$scriptname?provision=$empo'\">";
}

############ ta reda på slutarporterade veckor

$SQL = qq|Select week, checked_by, timebank
          From weeks
          Where (userid = $empo) AND year = $year
          Order By week|;
&SQL_Execute($SQL);

my(%fakvecka, %rapvecka, %tidbank);
while ( my ($week,$kontroll,$tid) = &SQL_Fetch() ) {
  $fakvecka{$week} = $EmpNames{$kontroll};
  $rapvecka{$week} = $kontroll;
  $tidbank{$week} = $tid;
}

##########################################

print "<h3>Report horaire pour $EmpNames{$empo} semaines $week1 - $week2, $year</h3>\n";
print "<table width=\"1000\"><tr><td>\n";

if (!$user_role) {
	print "<form method=\"post\" action=\"$scriptname?reports=1&markera=1\">\n";
}

# skriv ut röda dagar med röd färg
$SQL = qq|Select week, freedays, hours, comment
          From workhours_$year|;
&SQL_Execute($SQL);
#@red_weeks[1..52] = [];
#@arbetstid_weeks[1..52] = 40;
my(@red_weeks, @arbetstid_weeks);
while ( my($week, $red, $arbetstid, $komm) = &SQL_Fetch ) {
	my @reddays = split(',',$red);
	$red_weeks[$week] = \@reddays;
	@arbetstid_weeks[$week] = $arbetstid;
}

$SQL = qq|Select p.projid, c.name, p.description
          From projects p, customers c
          Where (p.custid = c.custid)
          Order By p.projid|;
&SQL_Execute($SQL);
my %proj_beskr;
while ( my($projnr, $kund, $beskr) = &SQL_Fetch() ) {
  $proj_beskr{$projnr} = " - $kund $beskr";
  $proj_beskr{$projnr} =~ s/ /&nbsp;/g;
}
$proj_beskr{400} = 'Absent';

$SQL = qq|Select actid, name
          From activities
          Order By name|;
&SQL_Execute($SQL);
my %lopnr_beskr;
while ( my($lopnr, $lopnamn) = &SQL_Fetch() ) {
  $lopnr_beskr{ $lopnr } = $lopnamn;
}

my $i = 501;
foreach ( @absent_vals ) {
	$lopnr_beskr{ $i++ } = $_;
}

$SQL = qq|Select week,row,projid,actid,timecode,monday,tuesday,wednesday,thursday,friday,saturday,sunday,theme,commentaire
          From reports_$year
          Where (week Between $week1 And $week2) And (userid = $empo)
          Order By week,row|;
&SQL_Execute($SQL);

my(@rows, $oldweek, $checkbolin, $tahandomsista);
my $first = 0;
#####################################################################
while ( my @row = &SQL_Fetch ) {
  my $curweek = $row[0];

  if (!$first++) {
  	$oldweek = $curweek;
  }

  for (5..11) {
    # skriv ut 7.0 som 7, 7.5 som 7.5
    if (int($row[$_]) == $row[$_]) { $row[$_] = int($row[$_]); }
    if ( $row[$_] == 0 ) { $row[$_] = ''; }
  }

  if ($curweek != $oldweek) {

SISTA:

  	my $week = $rows[0][0];

		my @red_day = ('','','','','','');
		my @reddays = @{$red_weeks[$week]};
		foreach (@reddays) {
		  $red_day[$_] = 'bgcolor="red"';
		}


#################################
		print "<h2>$week</h2>";
    print "<table border=\"0\" cellspacing=\"1\" cellpadding=\"2\" width=\"970\" class=table_border>\n";
    print "<tr class=tr_header>\n";
    print "  <td width=\"150\">Projet</td>\n";
    print "  <td width=\"100\">Activité</td>\n";
    print "  <td width=\"50\">Timecode</td>\n";
    print "  <td width=\"30\" $red_day[1]>Lun</td>\n";
    print "  <td width=\"30\" $red_day[2]>Mar</td>\n";
    print "  <td width=\"30\" $red_day[3]>Mer</td>\n";
    print "  <td width=\"30\" $red_day[4]>Jeu</td>\n";
    print "  <td width=\"30\" $red_day[5]>Ven</td>\n";
    print "  <td width=\"30\">Sam</td>\n";
    print "  <td width=\"30\">Dim</td>\n";
    print "  <td width=\"30\" align=\"center\">Tot</td>\n";
    print "  <td width=\"70\">Thème</td>\n";
    print "  <td>Commentaires</td>\n";
    print "</tr>\n";

    my @botSum = (0,0,0,0,0,0,0);
    my $totsum = 0;

    foreach (@rows) {
    	my @raden = @{$_};

    	my $sum = sprintf "%4.1f", $raden[5]+$raden[6]+$raden[7]+$raden[8]+$raden[9]+$raden[10]+$raden[11];
  		$totsum += $sum;
  		if (int($sum) == $sum) { $sum = int($sum); }
  		if ($sum == 0) { $sum = ''; }

  		for (5..11) {
  			$botSum[$_ - 5] += $raden[$_];
  		}

    	my $tidkod = '';
    	my $project;
    	if ($raden[2] != 400) {
    		$project = $raden[2] . " " . $proj_beskr{$raden[2]};
    		if ($raden[4] >= 0) {
    			$tidkod = $tidkoder[$raden[4]];
    		}
    	}
    	else {
    		$project = '<font color=red>' . $proj_beskr{$raden[2]} . '</font>';
    	}

    	print "<tr>\n";
		  print "  <td>$project</td>\n";
		  print "  <td>$lopnr_beskr{$raden[3]}&nbsp;</td>\n";
		  print "  <td>$tidkod&nbsp;</td>\n";
		  print "  <td align=right>$raden[5]&nbsp;</td>\n";
		  print "  <td align=right>$raden[6]&nbsp;</td>\n";
		  print "  <td align=right>$raden[7]&nbsp;</td>\n";
		  print "  <td align=right>$raden[8]&nbsp;</td>\n";
		  print "  <td align=right>$raden[9]&nbsp;</td>\n";
		  print "  <td style=\"background-color:lightyellow;\">$raden[10]&nbsp;</td>\n";
		  print "  <td style=\"background-color:lightyellow;\">$raden[11]&nbsp;</td>\n";
		  print "  <td align=\"center\">$sum</td>\n";
		  print "  <td><i>$raden[12]</i></td>\n";
		  print "  <td><i>$raden[13]</i></td>\n";
		  print "</tr>\n";

    }
    print "<tr class=tr_header>";
    print "  <td colspan=3>&nbsp;</td>\n";
    print "  <td align=\"center\">$botSum[0]</td>\n";
		print "  <td align=\"center\">$botSum[1]</td>\n";
		print "  <td align=\"center\">$botSum[2]</td>\n";
		print "  <td align=\"center\">$botSum[3]</td>\n";
		print "  <td align=\"center\">$botSum[4]</td>\n";
		print "  <td align=\"center\">$botSum[5]</td>\n";
		print "  <td align=\"center\">$botSum[6]</td>\n";
    print "  <td align=\"center\"><font color=red>$totsum</font></td>\n";
    print "  <td align=right colspan=\"2\">[$arbetstid_weeks[$week]] Tidbank: $tidbank{$week}</td>\n";
    print "</tr></table>\n";

		if    ($rapvecka{$week} > 0) {
		  print "<p>Semaine controlée par $fakvecka{$week}</p>\n"; }
		elsif ($rapvecka{$week} == -1 && !$user_role) {
			print "<p>Marquer la semaine comme controlée <input type=\"checkbox\" name=\"kollat$week\" value=\"1\"></p>\n";
		  $checkbolin = 1;
		}
		else {
			if ($rapvecka{$week} != -1) {
		  	print "<p><font color=\"red\">Semaine non finalisée</font></p>\n";
			}
		}

#################################
  	@rows = ();

  	$oldweek = $curweek;
  }
	push @rows, \@row;

}

goto SISTA if (!$tahandomsista++);

if (!$user_role) {
	print "<input type=\"hidden\" name=\"empo\" value=\"$empo\">\n";
	print "<input type=\"hidden\" name=\"week1\" value=\"$week1\">\n";
	print "<input type=\"hidden\" name=\"week2\" value=\"$week2\">\n";
	print "<input type=\"hidden\" name=\"year\" value=\"$year\">\n";
	if ($checkbolin  ) {
	  print "<input type=\"submit\" name=\"vidi\" value=\"Marquer les semaines comme controlée par $EmpNames{$admin}\" >\n";
	}
	print "</form>\n";
}


return;
INGA_RADER_I_INTERVALLET:
print "<p>Inga tidrapporter i intervallet !</p>\n";

}

#####################################################################################
sub Provision {

my $empo = shift;

$user = $empo || $user;

my $year = $qform{'year'} || $lastyear;
&YearSelect("provision=$user", $year);

my $month = $form{'month'} || 1;

&NamnLista($user);

if ($AccessRight > 0) {
	print "<input type=button value=\"Show yearly plan\" onClick=\"javascript:document.location.href='$scriptname?visayear=$user&year=$year'\"><br>\n";
}

print "<table width=\"1000\"><tr><td>\n";
print "<h3>Monthly timereports $year with provision for $EmpNames{$user}\n";
print "  <a href=\"#\" title=\"Help\" onClick=\"helpWin('Provision')\"><img src=\"$RELPATH/help.gif\" width=\"35\" height=\"16\" border=\"0\" alt=\"Help\"></a></h3>\n";

$SQL = qq|Select Distinct projid
          From reports_$year
          Where userid = $user|;
&SQL_Execute($SQL);
my(@projekt, %proj_namn, %overtid_f);
while ( my $proj = &SQL_Fetch ) {
	push(@projekt, $proj);
}

my $projlist = join(',', @projekt);

if ($projlist) {
	$SQL = qq|Select p.projid, c.name, p.description, overtime1, overtime2
	          From projects p, customers c
	          Where p.custid = c.custid And p.projid In ($projlist)|;
	&SQL_Execute($SQL);
	while ( my($proj, $kund, $beskr, $ot1, $ot2) = &SQL_Fetch ) {
			$proj_namn{$proj} = "$kund - $beskr";
			$overtid_f{$proj} = [2, $ot1, $ot2, 1, 1];
	}
}
$proj_namn{400} = 'Absent';
$overtid_f{400} = [1,1,1,1,1];

$SQL = qq|Select actid, name
          From activities|;
&SQL_Execute($SQL);
my %lopnr_namn;
while ( my($lopnr, $namn)  = &SQL_Fetch ) {
	$lopnr_namn{$lopnr} = $namn;
}

$SQL = qq|Select Max(week)
          From reports_$year
          Where userid = $user|;
&SQL_Execute($SQL);
my $months;
if ( my $week = &SQL_Fetch + 0 ) {
	$months = int($week*7/30.5)+1;
}
else {
	print "<br><h3>$EmpNames{$user} har ej rapporterat veckor år $year</h3>";
	return;
}

my $m;
for ($m=1; $m<=$months; $m++){
	$month = $m;
my($week1, $week2, $day1, $day2) = &Month2Week($month,$year);
#my @days = ('','Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag','Söndag');

print qq|<p><b>$Months_W[$month] week $week1 $Days[$day1] - week $week2 $Days[$day2]</b></p>

<table  cellspacing="0" cellpadding="2" class=table_border>
<tr class=tr_header>
	<td width="40">Semaine</td>
	<td width="200">Projet</td>
	<td width="80">Activité</td>
	<td width="50">Timecode</td>
	<td width="150">Commentaire</td>
	<td width="60">Heures</td>
</tr>
|;

# plocka ut arvodet för gällande period
my %arvode;
$SQL = qq|Select projid, fee
          From fees_$year
          Where userid = $user And ($month Between month1 And month2)|;
&SQL_Execute($SQL);
while ( my($proj, $arv) = &SQL_Fetch ) {
	$arvode{$proj} = $arv;
}

$SQL = qq|Select week, projid, actid,timecode,theme,monday,tuesday,wednesday,thursday,friday,saturday,sunday
          From reports_$year
          Where userid = $user And (week Between $week1 And $week2)
          Order By week,projid,row|;
&SQL_Execute($SQL);

my $totsum = 0;
my(%proj_sum, %overtid, $skip_first, $oldweek);
while ( my($week, $proj, $lopnr, $tidnr, $theme, @days) = &SQL_Fetch ) {
  my $d1 = 0; my $d2 = 7;
  if    ($week == $week1) { $d1 = $day1 - 1; }
  elsif ($week == $week2) { $d2 = $day2 - 1; }
  my $sum = 0;
  for ($d1..$d2) {
    $sum += $days[$_] ;
  }
  $totsum += $sum;
  $proj_sum{$proj} += $sum;

  if ($tidnr > 0) {
  	$overtid{$proj} += ( ${$overtid_f{$proj}}[$tidnr]  - 1)*$sum;
  }

  if (!$skip_first++) {
  	$oldweek = $week;
  }

  if ($week != $oldweek) {
  	$oldweek = $week;
  	print qq|<tr class=tr_header><td colspan="6"><img src="dummy.gif" width="10" height="1"></td></tr>|;
  } elsif ($skip_first >1 ) {
  	$week = '';
  }

  print qq|
  	<tr>
  		<td align="center"><b>$week</td>
  		<td>$proj $proj_namn{$proj}</td>
  		<td>$lopnr_namn{$lopnr}</td>
  		<td>$tidkoder[$tidnr]</td>
  		<td><i>$theme</i></td>
  		<td align="center">$sum</td>
    </tr>|;
}

print qq|
  	<tr>
  		<td colspan="5">&nbsp;</td>
  		<td align="center"><b>$totsum</td>
    </tr>|;

print qq|</table>|;

if ($PROVISION || 1) {
	print qq|<br>
	<table cellspacing="1" cellpadding="1" class=table_border>
	<tr class=tr_header>
		<td width="50">Project</td>
		<td width="50">Hours</td>
		<td width="30">Fee</td>
		<td width="80">Sum [\€]</td>
	</tr>
	|;
	my $totsum = 0;
	my $tot_ot = 0;
	my($proj, $sum2);
	foreach $proj(sort {$a <=> $b} keys %proj_sum) {
		my $sum = $proj_sum{$proj};
		if ( my $ot = $overtid{$proj} ) {
			$tot_ot += $ot;
			$sum += $ot;
			$sum2 = "$sum [<font color=red>$ot</font>]";
		}
		else {
			$sum2 = $sum;
		}
		$sum = $proj_sum{$proj}*$arvode{$proj};
		$totsum += $sum;

	  print qq|
  	<tr>
  		<td>$proj $proj_namn{$proj}</td>
  		<td>$sum2</td>
  		<td>$arvode{$proj}</td>
  		<td align="center">$sum</td>
    </tr>|;
	}
	$totsum = &Format($totsum);
	print qq|
	  	<tr class=tr_header>
	  		<td colspan="3">&nbsp;</td>
	  		<td align="center"><b>$totsum</td>
	    </tr>
	   </table>|;

	undef %proj_sum;
}

}
print "</td></tr></table>\n";

}


#####################################################################################
sub ProjektPlanering($$){

my($projekt, $year) = @_;

my $SQL = qq|Select userid, name, surname
             From users|;
&SQL_Execute($SQL);
%EmpNames = ();
while ( my ($userid, $name, $surname, $arv ) = &SQL_Fetch() ) {
	$name =~s/ +$//g;
	$surname =~s/ +$//g;
	$surname =~s/#$//g;
  $EmpNames{$userid} = "$name&nbsp;$surname";
}

$SQL = qq|Select f.userid, month1, month2, fee, percentage
          From fees_$year f, projectmember p
          Where  f.projid = $projekt And f.projid=p.projid And f.userid=p.userid
          Order By f.userid, f.projid, month1|;
&SQL_Execute($SQL);

my @colors = ('#FFFF00', '#FFCC00', '#FF9900', '#FF6600', '#FF3300', '#CC0000');

print qq|<br><br><table width=885 border=0 cellspacing=0 cellpadding=0 class=table_gant>
         <tr class=tr_header>
           <td rowspan=2 width=150>Membres</td>|;

for (1..12) {
	print "<td width=60 align=center>$_</td>"
}
print "</tr><tr class=tr_header>";

for (1..12) {
	my $bgcol = !($_ % 2) ?  'lightgrey' : '#808080';
	print "<td align=center style=\"background-color: $bgcol\">$Months_W[$_]</td>"
}
print "</tr>";

my($skip_first, $old_user, $last_userid, @proj, %rader, $ta_sista);
while ( my($userid, $month1, $month2, $arvode, $omfattning) = &SQL_Fetch ) {
	if (!$skip_first++) {
		$old_user = $userid;
	}
	$last_userid = $userid;
	if ($userid != $old_user) {
HOPPA_IN:
		my $rowspan = $rader{$old_user}+1-1;
		print qq|<tr><td colspan=13 bgcolor=#808080 height=4></td></tr>\n|;
		my $r = 0;
		my $user;
		foreach $user(@proj){
			my $pre_row = (!$r++) ? "<td rowspan=$rowspan bgcolor=white>&nbsp;$old_user - $EmpNames{$old_user}</td>" : '';
			my @tuple = @{$user};
			my $colspan = $tuple[1]-$tuple[0]+1;
			my $kr = $tuple[2];
			my $inledn;
			my $omfattn = $tuple[3];
			if ( 0 < ($inledn = $tuple[0]-1) ){ $inledn="<td colspan=$inledn bgcolor=lightgrey>&nbsp;</td>\n"; }
			else                              { $inledn = ''; }
			if ($colspan>1){ $colspan = "colspan=$colspan"; }
			else           { $colspan = ''; }
			my $rest = 12 - $tuple[1];
			if ($rest = 12 - $tuple[1]) { $rest = "<td colspan=$rest bgcolor=lightgrey>&nbsp;</td>"; }
			else                        { $rest = ''; }
			my $i = int($omfattn/20);
			if ($i<0 || $i>5) { $i = 2; }
			my $color = $colors[$i];
			print "<tr>$pre_row$inledn<td $colspan align=center bgcolor=$color>$omfattn% [$kr]</td>$rest</tr>\n";
		}
		@proj = ();
		$old_user = $userid;
	}
	push(@proj, [$month1, $month2, $arvode, $omfattning] );
	$rader{$userid}++;
}

if (!$ta_sista++){
	$old_user = $last_userid;
	goto HOPPA_IN;
}

print qq|</table>|;

}

#####################################################################################
sub UserPlanering($$){

my($user, $year) = @_;

$SQL = qq|Select p.projid, month1, month2, fee, percentage, c.name, p.description
          From fees_$year f, projects p, customers c
          Where  userid = $user And p.projid = f.projid And p.custid = c.custid
          Order By p.projid, month1|;
&SQL_Execute($SQL);

my @colors = ('#FFFF00', '#FFCC00', '#FF9900', '#FF6600', '#FF3300', '#CC0000');

print qq|<br><br><table border=0 class=table_gant cellspacing=0 cellpadding=0>
         <tr class=tr_header>
           <td rowspan=2 width=150 >&nbsp;Projet</td>|;

for (1..12) {
	print "<td width=60 align=center>$_</td>"
}
print "</tr><tr class=tr_header>";

for (1..12) {
	my $bgcol = !($_ % 2) ?  'lightgrey' : '#808080';
	print "<td align=center style=\"background-color: $bgcol\">$Months_W[$_]</td>"
}
print "</tr>";

my(%proj_beskr, $skip_first, $old_proj, $last_proj, @projekt, %rader, $ta_sista, $inledn);
while ( my($proj, $month1, $month2, $arvode, $omfattning, $kund, $beskr) = &SQL_Fetch ) {
	$proj_beskr{$proj} = "$kund $beskr";
	$proj_beskr{$proj} =~ s/ /&nbsp;/g;
	if (!$skip_first++) {
		$old_proj = $proj;
	}
	$last_proj = $proj;
	if ($proj != $old_proj) {
HOPPA_IN:
		my $rowspan = $rader{$old_proj}+1-1;
		print qq|<tr><td colspan=13 bgcolor=#808080 height=2></td></tr>\n|;
		my $r = 0;
		my $proj2;
		foreach $proj2(@projekt){
			my $pre_row = (!$r++) ? "<td rowspan=$rowspan bgcolor=white>$old_proj&nbsp;-&nbsp;$proj_beskr{$old_proj}</td>" : '';
			my @tuple = @{$proj2};
			my $colspan = $tuple[1]-$tuple[0]+1;
			my $kr = $tuple[2];
			my $omfattn = $tuple[3];
			my $rest;
			if ( 0 < ($inledn = $tuple[0]-1) ){ $inledn="<td colspan=$inledn bgcolor=lightgrey>&nbsp;</td>\n"; }
			else                              { $inledn = ''; }
			if ($colspan>1){ $colspan = "colspan=$colspan"; }
			else           { $colspan = ''; }
			if ($rest = 12 - $tuple[1]) { $rest = "<td colspan=$rest bgcolor=lightgrey>&nbsp;</td>"; }
			else                        { $rest = ''; }
			my $color = $colors[int $omfattn/20];
			print "<tr>$pre_row$inledn<td $colspan align=center bgcolor=$color>$omfattn% [$kr]</td>$rest</tr>\n";
		}
		@projekt = ();
		$old_proj = $proj;
	}
	push(@projekt, [$month1, $month2, $arvode, $omfattning] );
	$rader{$proj}++;
}

if (!$ta_sista++){
	$old_proj = $last_proj;
	goto HOPPA_IN;
}

print qq|</table>|;

}

#####################################################################################
sub DeltagareYear {

my $year = $qform{'deltagareyear'};
$SQL = qq|Select p.projid, c.name, p.description
          From projects p, customers c
          Where p.custid = c.custid|;
&SQL_Execute($SQL);
my %projekt_beskr;
while ( my($proj, $kund, $beskr) = &SQL_Fetch ) {
	$projekt_beskr{$proj} = "$kund - $beskr";
}

$SQL = qq|Select userid, projid, month1, month2, fee, percentage
          From fees_$year
          Order By userid, projid, month1|;
&SQL_Execute($SQL);
my($old_user, %users, @proj, $old_user2, $skip_first);
while ( my($userid, $projekt, $month1, $month2, $arvode, $omfattning) = &SQL_Fetch ) {
	if (!$skip_first++) {
		$old_user = $userid;
	}
	if ($userid != $old_user) {
		my @proj2 = @proj;
		undef @proj;
		$users{$old_user} = \@proj2;
		$old_user = $userid;
	}
	push(@proj, [$projekt, $month1, $month2, $arvode, $omfattning] );
	$old_user2 = $userid;
}
my @proj2 = @proj;
$users{$old_user2} = \@proj2;

&NamnLista;
my @colors = ('#FFFF00', '#FFCC00', '#FF9900', '#FF6600', '#FF3300', '#CC0000');

print qq|<br><br>
   <table border=0 bgcolor=lightgrey cellspacing=0 cellpadding=0 class=table_yearproject>
   <tr class=tr_header_proj>
     <td width=200>Projet</td>|;

for (1..12) {
	print "<td width=50 align=center>$_</td>"
}
print "</tr>";

my($user);
foreach $user(sort {$a<=>$b} keys %users){
	my @proj2 = @{ $users{$user} };
	my $old_proj;
	print qq|
	<tr>
		<td colspan=13 bgcolor=#808080 height=20>$user - $EmpNames{$user}</td>
	</tr>|;
	foreach (@proj2){
		my @tuple = @{$_};
		my $colspan = $tuple[2]-$tuple[1]+1;
		my $proj = $tuple[0];
		my $kr = $tuple[3];
		my $omfattn = $tuple[4];
		my $i = int($omfattn/20);
		if ($i<0 || $i>5) { $i = 2; }
		my $color = $colors[$i];
		my $inledn;
		if ( 0 < ($inledn = $tuple[1]-1) ){ $inledn="<td colspan=$inledn bgcolor=lightgrey>&nbsp;</td>"; }
		else                              { $inledn = ''; }
		if ($colspan>1){ $colspan = "colspan=$colspan"; }
		else           { $colspan = ''; }
		my $proj_line;
		if ($proj != $old_proj) {
			$proj_line = "$proj $projekt_beskr{$proj}";
			$old_proj = $proj;
		}
		else {
			$proj_line = '&nbsp;';
		}
		my $rest;
		if ($rest = 12 - $tuple[2]) { $rest = "<td colspan=$rest bgcolor=lightgrey>&nbsp;</td>"; }
		else                        { $rest = ''; }
		print qq|
		<tr>
			<td width=200 class=td_projline>$proj_line</td>$inledn<td $colspan align=center bgcolor=$color>$omfattn%</td>$rest
			</tr>
		|;
	}
}

print qq|</table>|;

}

#####################################################################################
sub YearSelect($$) {
	my ($action, $year) = @_;
	print "<form method=\"get\" action=\"$scriptname\">\n";
	print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"1\" bgcolor=\"#000000\"><tr><td>\n";
	print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" >\n";
	print "<tr><td class=\"td_form\">\n";
	print "Année: <select name=\"year\">\n";
	my $sel_year = $year || $lastyear;
	for ($FIRST_YEAR..$lastyear+1){
		my $sel = ($_ == $sel_year) ? 'selected' : '';
		print "<option $sel value=\"$_\">$_</option>\n";
	}
	print "  </select>\n";
	my ($name, $value) = split( /=/, $action);
	print "  <input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
	print "  <input type=\"submit\" name=\"Submit\" value=\"OK\" >\n";
	print "</td></tr></table>\n";
	print "</td></tr></table>\n";
	print "</form>\n";
}


#####################################################################################
sub MainSwitch {

if ( $AccessRight == 0 ) {

	if ( $qform{'saveweek'} || $form{'transmitter'} ) {
    &SparaVecka;
    print qq|<script>
      				parent.saved();
      			 </script>
    |;
    exit;
  }

  print "<script language=\"JavaScript\" src=\"$RELPATH/rodeo3.js\"></script>\n";

  if    ( $qform{'config'} ) {
    &UserConfig;
  }
  elsif ( $visa_year ) {
    print "<body bgcolor=\"#FFCC66\" leftmargin=\"30\">\n";

    print "<input type=button value=\"Show yearly time reports\" onClick=\"javascript:document.location.href='$scriptname?visatidkort=1'\">\n";
		print "<input type=button value=\"Show yearly provision\" onClick=\"javascript:document.location.href='$scriptname?provision=1'\">\n";

    &YearView($user);
  }
  elsif ( $qform{'visatidkort'} ) {
  	print "<body bgcolor=\"#FFCC66\" leftmargin=\"30\">\n";

  	print "<input type=button value=\"Show yearly plan\" onClick=\"javascript:document.location.href='$scriptname?visayear=$lastyear'\">\n";
		print "<input type=button value=\"Show yearly provision\" onClick=\"javascript:document.location.href='$scriptname?provision=1'\">\n";
    my $year = $qform{'year'};
		&YearSelect("visatidkort=$user", $year);
		my($week1, $week2) = ($qform{'week1'}, $qform{'week2'});
  	&UserTidKort($user, $week1, $week2, $year);
  }
  elsif ( $qform{'provision'} ) {
  	print "<body bgcolor=\"#FFCC66\" leftmargin=\"30\">\n";

  	print "<input type=button value=\"Show yearly plan\" onClick=\"javascript:document.location.href='$scriptname?visayear=$lastyear'\">\n";
		print "<input type=button  value=\"Show yearly provision\" onClick=\"javascript:document.location.href='$scriptname?visatidkort=1'\">\n";

  	&Provision;
  }
  else {
    &TidRapportera;
  }

}
elsif ( $AccessRight > 0 ) {

  my $showemps        = $qform{'showemps'};
  my $showprojects    = $qform{'projects'};
  my $faktura         = $qform{'faktura'};
  my $kunder          = $qform{'kunder'};
  my $allafakturor    = $qform{'allafakturor'};
  my $visa_project_year = $qform{'visaprojyear'};
  my $printer         = $qform{'printer'};
  my $lopnummer       = $qform{'lopnummer'};
  my $projectmembers  = $qform{'projectmembers'};
  my $projektdeltagare = $qform{'projektdeltagare'};
  my $almanacka       = $qform{'almanacka'};
  my $almanacka2      = $qform{'almanacka2'};
  my $prognos         = $qform{'prognos'};
  my $showtidbank     = $qform{'showtidbank'};
  my $deltagareyear   = $qform{'deltagareyear'};
  my $semester        = $qform{'semester'};

  my $year        = $qform{'year'};

  if ( $almanacka2  || $semester || $deltagareyear) {
  	if ($almanacka2){
    	&Almanacka2($almanacka2); }
    elsif ($semester){
    	&Semester; }
    elsif ($deltagareyear){
      &DeltagareYear }

    &SQL_Close;
    exit;
  }
  # &PrintHeader(0,1);

  if ($printer) {
    &PrinterFriendly;
    &SQL_Close;
    exit;
  }

  &PrintAdminMenu;
  #print "<div id=content>";

  if ($faktura) {
    &RapporteraProjekt; }
  elsif ($showemps) {
    &PrintEmployees; }
  elsif ($kunder) {
    &PrintKunder; }
  elsif ($showprojects) {
    &PrintProjects; }
  elsif ($lopnummer) {
    &PrintLopnummer; }
  elsif ($qform{'reports'}) {
    &VisaTidrapporter; }
  elsif ($allafakturor) {
    &Fakturera; }
  elsif ($visa_year) {
    &YearView($visa_year);
    print "<br><input type=button  value=\"Show yearly provision\" onClick=\"javascript:document.location.href='$scriptname?provision=$visa_year&year=$year'\">\n";
  }
  elsif ($visa_project_year) {
    &ProjectYearView($visa_project_year); }
  elsif ($projectmembers) {
    &ProjectMembers; }
  elsif ($projektdeltagare) {
    &ProjektDeltagare($projektdeltagare); }
  elsif ($almanacka) {
    &Almanacka; }
  elsif ($prognos) {
    &Planering; }
  elsif ($showtidbank) {
    &Tidbank($showtidbank); }
  elsif ($qform{'super'} && $user == 666) {
   # &SuperUser; ´
  }
  elsif ($provision = $qform{'provision'}) {
    &Provision($provision); }
  else {
    if ($user == 666) {
      &SuperUser;
    }
  }
 print "</div>";
 print "<div id=\"footer\">$FOOTER</div>";

 print "</body></html>\n";
}

}