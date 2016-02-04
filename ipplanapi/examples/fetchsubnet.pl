#!/usr/bin/perl

# IPplan v2.92
# Aug 24, 2001
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#


# Frontier: requires http://search.cpan.org/search?dist=Frontier-RPC
use Frontier::Client;

# location of XML_RPC server for IPplan
# if the server requires authentication, you will need to embed the
# userid and password into the URL. If anonymous access is allowed,
# remove the userid:password@ portion from the URL
my $serverURL='http://userid:password@localhost/~richarde/iptrackdev/iptrackapi/server.php';

my $baseindex = $ARGV[0];

# setup connection to server
my $client = Frontier::Client->new( 'url' => $serverURL,
		'debug' => 0, 'encoding' => 'iso-8859-1' );
# and method to call
# returns an array of hashes - array corresponds to rows in database,
# hashes correspond to columns
my $resp = $client->call("ipplan.FetchSubnet", $baseindex);

# display some debug info
#use Data::Dumper;
#print Dumper($resp);

# print result
foreach $row (@$resp) {
   printf("%s\t%s\t%s\t%s\t%s\n", @$row{ipaddr}, @$row{userinf}, @$row{location}, @$row{descrip}, @$row{lastmod});
}

