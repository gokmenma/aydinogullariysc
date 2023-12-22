

<div class="header clearfix">


<div class="header-right">


	<div class="brand-logo">
            <a href="index.php">
                <img width="70" src="<?php echo set("logo"); ?>" alt="" class="mobile-logo">
            </a>
        </div>


        <div class="menu-icon">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
</div>


        <div class="user-info-dropdown">
            <div class="dropdown">
                <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <span class="user-icon"><i class="fa fa-user-o"></i></span>
                    <span class="user-name"><?php echo sesset("name") . " " . sesset("surname"); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="index.php?p=edit-user&uid=<?php echo sesset("id"); ?>"><i
                            class="fa fa-user-md" aria-hidden="true"></i> Profil Düzenle</a>
                    <a class="dropdown-item" href="index.php?p=settings"><i class="fa fa-cog" aria-hidden="true"></i>
                        Ayarlar</a>

                    <a class="dropdown-item" href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Çıkış
                        Yap</a>
                </div>
            </div>
        </div>
		<!-- <div class="page-header"> -->
					<!-- <div class="row"> -->
					<div class="breadcrumb ml-4">
					<nav aria-label="breadcrumb" role="navigation">
								<ol class="breadcrumb">
 <li class="breadcrumb-item"><a href="index.php">
										<?php echo set("site_title"); ?></a></li>
									<li class="breadcrumb-item active" aria-current="page"><?php echo $pdat["p_title"]; ?>

									</li>
								</ol>
							</nav>
						</div>
					<!-- </div> -->
				<!-- </div> -->
        <!--
			<div class="user-notification">
				<div class="dropdown">
					<a class="dropdown-toggle no-arrow" href="#" role="button" data-toggle="dropdown">
						<i class="fa fa-bell" aria-hidden="true"></i>
						<span class="badge notification-active"></span>
					</a>
					<div class="dropdown-menu dropdown-menu-right">
						<div class="notification-list mx-h-350 customscroll">
							<ul>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
								<li>
									<a href="#">
										<img src="vendors/images/img.jpg" alt="">
										<h3 class="clearfix">John Doe <span>3 mins ago</span></h3>
										<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed...</p>
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>-->
    </div>
</div>