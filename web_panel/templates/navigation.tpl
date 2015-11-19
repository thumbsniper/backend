{*
 Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*}

<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">{$site.title}</a>
            </div>
            <!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
                <li><a href="{$webUrl}"><i class="fa fa-long-arrow-left fa-fw"></i> back to Main Site</a></li>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
			<i class="fa {if isset($oauth)}{if $oauth->getProvider() == "google"}fa-google{elseif $oauth->getProvider() == "twitter"}fa-twitter{/if}{else}fa-user{/if} fa-fw" {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())} style="color: red;" {/if}></i>
			{if isset($account)} {$account->getFirstName()} {/if}<i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
			{if isset($account)}
			{if $account->isAdmin() == true}
                    {if $account->isActAsAdmin()}
                    		<li><a href="/index.php?admin=0">disable AdminMode</a></li>
                	{else}
                    		<li><a href="/index.php?admin=1">enable AdminMode</a></li>
                	{/if}
                <li class="divider"></li>
            {/if}
                        <li><a href="/?logout=1"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
			{else}
			<li><a href="/pages/login.php"><i class="fa fa-sign-in fa-fw"></i> Login</a>
			{/if}
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->

	    {if isset($account)}
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                        <li>
                            <a href="/pages/statistics.php"><i class="fa fa-bar-chart-o"></i> Statistics</a>
                        </li>
                        {/if}
                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                        <li>
                            <a href="#"><i class="fa fa-sitemap fa-fw"></i> Accounts<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                    <li>
                                        <a href="/pages/allaccounts.php"><i class="fa fa-table fa-fw" style="color: red;"></i> All Accounts</a>
                                    </li>

                                <li>
                                    <a href="/pages/accountinfo.php"><i class="fa fa-table fa-fw"></i> Account information</a>
                                </li>
                            </ul>
                        </li>
                        {else}
                            <li>
                                <a href="/pages/accountinfo.php"><i class="fa fa-table fa-fw"></i> Account information</a>
                            </li>
                        {/if}
                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                        <li>
                            <a href="#"><i class="fa fa-camera fa-fw"></i> Targets<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                        <a href="/pages/alltargets.php"><i class="fa fa-table fa-fw" style="color: red;"></i> All Targets</a>
                                </li>
                                <li>
                                        <a href="/pages/mytargets.php"><i class="fa fa-table fa-fw"></i> My Targets</a>
                                </li>
                                {if $smarty.server.PHP_SELF == '/pages/targetinfo.php'}
                                <li>
                                    <a href="{$smarty.server.REQUEST_URI}"><i class="fa fa-table fa-fw"></i> Target information</a>
                                </li>
                                {elseif $smarty.server.PHP_SELF == '/pages/imageinfo.php'}
                                    <li>
                                        <a href="{$smarty.server.REQUEST_URI}"><i class="fa fa-table fa-fw"></i> Image information</a>
                                    </li>
                                {/if}
                            </ul>
                        </li>
                        {else}
                            <li>
                                <a href="/pages/mytargets.php"><i class="fa fa-table fa-fw"></i> My Targets</a>
                            </li>
                        {/if}

                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                        <li>
                            <a href="#"><i class="fa fa-sitemap fa-fw"></i> Referrers<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
				<li>
					<a href="/pages/allreferrers.php"><i class="fa fa-table fa-fw" style="color: red;"></i> All Referrers</a>
				</li>
				<li>
                                        <a href="/pages/myreferrers.php"><i class="fa fa-table fa-fw"></i> My Referrers</a>
                                </li>
                                {if $smarty.server.PHP_SELF == '/pages/referrerinfo.php'}
                                    <li>
                                        <a href="{$smarty.server.REQUEST_URI}"><i class="fa fa-table fa-fw"></i> Referrer information</a>
                                    </li>
                                {/if}
			    </ul>
			</li>
                        {else}
                            <li>
                                <a href="/pages/myreferrers.php"><i class="fa fa-table fa-fw"></i> My Referrers</a>
                            </li>
                        {/if}

                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                            <li>
                                <a href="/pages/alluseragents.php"><i class="fa fa-table fa-fw"></i> User Agents</a>
                            </li>
                            {if $smarty.server.PHP_SELF == '/pages/useragentinfo.php'}
                                <li>
                                    <a href="{$smarty.server.REQUEST_URI}"><i class="fa fa-table fa-fw"></i> User Agent information</a>
                                </li>
                            {/if}
                        {/if}

                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                            <li>
                                <a href="/pages/targethostsblacklist.php"><i class="fa fa-table fa-fw"></i> Target hosts blacklist</a>
                            </li>
                        {/if}

                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                            <li>
                                <a href="#"><i class="fa fa-sitemap fa-fw"></i> Init<span class="fa arrow"></span></a>
                                <ul class="nav nav-second-level">
                                    <li>
                                        <a href="/pages/initalldummies.php"><i class="fa fa-image"></i> dummies</a>
                                    </li>
                                    <li>
                                        <a href="/pages/initallrobots.php"><i class="fa fa-image"></i> robots</a>
                                    </li>
                                    <li>
                                        <a href="/pages/initallbroken.php"><i class="fa fa-image"></i> broken</a>
                                    </li>
                                    <li>
                                        <a href="/pages/initallviolation.php"><i class="fa fa-image"></i> violation</a>
                                    </li>
                                </ul>
                            </li>
                        {/if}
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
	    {/if}
        </nav>
