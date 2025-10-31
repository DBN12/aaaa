#! c:\perl\bin\perl
# /usr/local/bin/perl


use DBI;
use strict;

$|= 1;


my $MYSQL = 1;
my $DSN          = 'rodeo' ;
my $host         = 'localhost'; # 'mysql';
my $opt_user     = 'root';
my $opt_password = '';

my $AUTO = !$MYSQL ? "IDENTITY(1,1)" : "AUTO_INCREMENT";

my($sth, $dbh);

my $year = 2005;

&SQL_Connect;

my $par = 0;

do {
	my $token = $ARGV[$par++];
	if    ($token eq 's') {
		&StateTables;
	}
	elsif ($token eq 'y') {
		$year = $ARGV[$par++];
	  &YearTables($year);
	}
	else {
		print "Incorrect option: $token\n";
	}
} while ($par <= $#ARGV);

#$SQL_Close;

################################################################################3
sub YearTables($) {
	my $year = shift;

	my $SQL = qq|CREATE TABLE reports_$year (
	          userid   INTEGER NOT NULL,
	          week     INTEGER NOT NULL,
	          projid   INTEGER NOT NULL,
	          actid    INTEGER NOT NULL,
	          timecode INTEGER DEFAULT 0,
	          row      INTEGER NOT NULL,
	          monday DECIMAL(3,1), tuesday DECIMAL(3,1), wednesday DECIMAL(3,1), thursday DECIMAL(3,1),
	          friday DECIMAL(3,1),saturday DECIMAL(3,1), sunday DECIMAL(3,1),
	          comment VARCHAR(60)
	          )|;
	&SQL_Execute($SQL);

  &SQL_Execute("CREATE UNIQUE INDEX r_userweekrow_idx ON reports_$year(userid, week, row)");
  &SQL_Execute("CREATE INDEX r_uid_idx     ON reports_$year(userid)");
  &SQL_Execute("CREATE INDEX r_week_idx    ON reports_$year(week)");
  &SQL_Execute("CREATE INDEX r_projnr_idx  ON reports_$year(projid)");

	print "Create reports_$year [$SQL]\n";

	$SQL = qq|CREATE TABLE invoices_$year (
	           projid INTEGER NOT NULL,
	           week1  INTEGER NOT NULL,
	           week2  INTEGER NOT NULL,
	           month  INTEGER,
	           amount INTEGER NOT NULL,
	           hours  INTEGER NOT NULL,

	           created_by   INTEGER NOT NULL,
	           date_created CHAR(10) NOT NULL,

	           posted_by    INTEGER NULL,
	           date_posted  CHAR(10) NULL,

	           payment_received CHAR(10) NULL,
	           payment_due      CHAR(10) NULL,

	           date_paid    INTEGER,
	           datum        CHAR(10),
	           ID           CHAR(10) PRIMARY KEY
	           )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX proj_w1w2_idx ON invoices_$year(projid, week1, week2)");
	print "Create fakturor_$year [$SQL]\n";

	$SQL = qq|CREATE TABLE fees_$year (
	           userid       INTEGER NOT NULL,
	           projid       INTEGER NOT NULL,
	           month1       INTEGER NOT NULL,
	           month2       INTEGER NOT NULL,
	           fee          INTEGER NOT NULL,
	           type         INTEGER,
	           percentage   INTEGER NOT NULL,
	           date_created TIMESTAMP,
	           ID           INTEGER PRIMARY KEY $AUTO
	           )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX u_p_m1_m2_idx ON fees_$year(userid, projid, month1, month2)");
	&SQL_Execute("CREATE INDEX userid_idx  ON fees_$year(userid)");
	&SQL_Execute("CREATE INDEX project_idx  ON fees_$year(projid)");
	print "Create fees_$year [$SQL]\n";

	$SQL = qq|CREATE TABLE workhours_$year (
	          week     INTEGER PRIMARY KEY,
	          freedays CHAR(10),
	          hours    INTEGER,
	          comment  VarCHAR(255)
	           )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE INDEX workhours_idx ON workhours_$year(week)");
	print "Create arbetstid_$year [$SQL]\n";

	# arbetstid innehåller en rad för varje vecka (1-53), från Rodeo sker endast Update
	for (1..53) {
	  $SQL = qq|Insert Into workhours_$year (week, hours)
	            Values ($_, 40)|;
	  &SQL_Execute($SQL);
	}

}
################################################################################3
sub StateTables {

	my $SQL = qq|CREATE TABLE users (
	           userid       INTEGER PRIMARY KEY,
	           name         VARCHAR(10),
	           surname      VARCHAR(15) NOT NULL,
	           authority    INTEGER DEFAULT 0,
	           password     CHAR(13) NOT NULL,
	           nick         VARCHAR(4),
	           timebank     INTEGER DEFAULT 0,
	           begindate    CHAR(8) NOT NULL,
	           resigned     CHAR(8),
	           loggedin     INTEGER,
	           current_year INTEGER,
	           email        VARCHAR(50)

	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX userid_idx ON users(userid)");
	print "Create employees [$SQL]\n";

	$SQL = qq|CREATE TABLE customers (
	          custid      INTEGER PRIMARY KEY,
	          name        VARCHAR(20) NOT NULL,
	          description VARCHAR(40)
	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX customer_idx ON customers(custid)");
	print "Create kunder [$SQL]\n";

	$SQL = qq|CREATE TABLE activities (
	          actid       INTEGER PRIMARY KEY,
	          name        VARCHAR(20) NOT NULL,
	          description VARCHAR(40),
	          maskid      INTEGER
	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX activity_idx ON activities(actid)");
	print "Create lopnummer [$SQL]\n";

	$SQL = qq|CREATE TABLE projects (
	          projid          INTEGER PRIMARY KEY,
	          custid          INTEGER NOT NULL,
	          description     VARCHAR(40) NOT NULL,
	          projectmanager  INTEGER NOT NULL,
	          hours           INTEGER,
	          closed          CHAR(8),
	          overtime1       DECIMAL(3,1),
	          overtime2       DECIMAL(3,1),
	          activities_mask INTEGER
	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX project_idx ON projects(projid)");
	print "Create project [$SQL]\n";

	# member, project, visible
	$SQL = qq|CREATE TABLE projectmember (
	          userid INTEGER NOT NULL,
	          projid INTEGER NOT NULL,
	          visible INTEGER
	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX member_proj_idx ON projectmember(userid, projid)");
	print "Create projectmember [$SQL]\n";

	$SQL = qq|CREATE TABLE timebank (
	          userid     INTEGER NOT NULL,
	          year       INTEGER NOT NULL,
	          week       INTEGER NOT NULL,
	          original   INTEGER NOT NULL,
	          changed    INTEGER NOT NULL,
	          withdrawal INTEGER NOT NULL,
	          comment    VarCHAR(80)
	          )|;
	&SQL_Execute($SQL);
	&SQL_Execute("CREATE UNIQUE INDEX u_y_w_idx ON timebank(userid, year, week)");

	print "Create tidbank [$SQL]\n";

	$SQL = qq|CREATE TABLE weeks (
	          userid     INTEGER NOT NULL,
	          year       INTEGER NOT NULL,
	          week       INTEGER NOT NULL,
	          timebank   INTEGER,
	          checked_by INTEGER DEFAULT -1)|;
	&SQL_Execute($SQL);

	&SQL_Execute("CREATE INDEX w_uid_idx      ON weeks(userid)                     ");
	&SQL_Execute("CREATE INDEX w_week_idx     ON weeks(year, week)                 ");
	&SQL_Execute("CREATE INDEX w_kontroll_idx ON weeks(checked_by)                   ");
	&SQL_Execute("CREATE UNIQUE INDEX uid_year_week_idx ON weeks(userid, year, week)");

	print "Create weeks [$SQL]\n";

}

#####################################################################################
sub SQL_Execute($) {
	my @params = @_;
	my $kommando = uc($params[0]);
	my $SQL  = qq| @params |;

	if ( $sth = $dbh->prepare($SQL) ) {
	  if ( my $rc = $sth->execute ) {
	  	return 1;
	  }
	  else {
	    print "\n\nCan't execute statement: $DBI::errstr";
	    print "\n[$SQL]\n";
	    return 0;
	  }
	}
	else {
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
  die $sth->errstr if $sth->err;
  $dbh->disconnect;
}

#####################################################################################

sub SQL_Connect {

  if ($MYSQL) {
		$dbh = DBI->connect("DBI:mysql:$DSN:$host",$opt_user,$opt_password,{ PrintError => 1})
	} else {
	 	$dbh = DBI->connect("DBI:ODBC:$DSN", $opt_user,$opt_password, { PrintError => 1}) ; #|| die "Can't connect: $DBI::errstr\n";
	}
}