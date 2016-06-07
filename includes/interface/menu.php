<div class="tnj-headermenu-outer">
    <div class="container">
        <div class="row tnj-headermenu-mobile hidden-sm hidden-md hidden-lg">
            <div class="col-md-12">
                <a href="http://www.kasigi.com/" title="Home"><img src="http://www.kasigi.com/wp-content/themes/tnjPortfolio2013/images/Small-White-Logo.png" alt="Tor N. Johnson Logo" class="tnj-headermenu-logo"></a>
                <nav class="navbar " role="navigation">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <div id="tnj-headermenu-mobile-search">
                            <form class="navbar-form navbar-left searchform" role="search" method="get" id="searchform3" action="http://www.kasigi.com/">
                                <label class="tnj-accessable-hide" for="s3">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search" name="s" id="s3">
				      <span class="input-group-btn">
					<button class="btn btn-default" type="submit">Search</button>
				      </span>
                                </div><!-- /input-group -->
                            </form>
                        </div><!-- end # tnj-headermenu-mobile-search -->
                    </div><!-- end navbar-header-->

                    <div class="collapse navbar-collapse navbar-ex1-collapse">
                        <ul class="nav navbar-nav">
                            <li class="menu-item">
                                <a title="View on GitHub" href="/table/">Data</a>
                            </li>
                            <li class="menu-item">
                                <a title="View on GitHub" href="/system/">System</a>
                            </li>
                            <li class="menu-item">
                                <a title="View on GitHub" href="https://github.com/kasigi/anTic" target="_blank">View Source on GitHub</a>
                            </li>
                        </ul>
                    </div>

                </nav>
            </div><!-- end col-md-12 -->
        </div><!-- row -->
        <div class="row tnj-headermenu  hidden-xs">
            <div class="col-md-12">
                <a href="/" title="Home"><img src="http://www.kasigi.com/wp-content/themes/tnjPortfolio2013/images/Small-White-Logo.png" alt="Tor N. Johnson Logo" class="tnj-headermenu-logo"></a>
                <nav>

                    <div class="menu-main-menu-container">
                        <ul class="nav navbar-nav">
                            <li class="menu-item">
                                <a title="View on GitHub" href="/#/table/">Data</a>
                            </li>
                            <li class="menu-item">
                                <a title="View on GitHub" href="/#/system/">System</a>
                            </li>
                            <li class="menu-item">
                                <a title="View on GitHub" href="https://github.com/kasigi/anTic" target="_blank">View Source on GitHub</a>
                            </li>
                        </ul>
                    </div>
                    <form class="navbar-form navbar-right hidden-sm hidden-md searchform" role="search" method="get" id="searchform" action="http://www.kasigi.com/">
                        <label class="tnj-accessable-hide" for="s">Search</label>
                        <div class="input-group">

                            <input type="text" class="form-control" placeholder="Search" name="s" id="s">
				      <span class="input-group-btn">
					    <button class="btn btn-default" type="submit">Search</button>
				      </span>
                        </div><!-- /input-group -->
                    </form>

                </nav>
            </div><!-- end col-md-12 -->
        </div><!-- row -->
    </div><!-- end container -->


    <div class="container visible-sm visible-md">
        <div class="row">
            <div class="col-md-12 tnj-headermenu-under">
                <form class="navbar-form navbar-right searchform" role="search" method="get" id="searchform2" action="http://www.kasigi.com/">
                    <label class="tnj-accessable-hide" for="s2">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search" name="s" id="s2">
				      <span class="input-group-btn">
					<button class="btn btn-default" type="submit">Search</button>
				      </span>
                    </div><!-- /input-group -->
                </form>
            </div><!-- end col-md-12 -->
        </div><!-- row -->
    </div><!-- end container -->


</div>

<div ng-controller="LoginController">
    <p>Email</p>
    {{userMeta}}
    <p ng-bind="userMeta.email"></p>
</div>
