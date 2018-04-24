<?php
        $title = 'Mr.Designer';
        $name = $_SERVER['REQUEST_URI'];
        $name = str_replace(".php", "", str_replace("/zaowuren/", "", $name));
        switch ($name) {
            case "index":
                $title = "Mr.Designer";
            break;
            case "about":
                $title = "公司 | Mr.Designer";
            break;
            case "project":
                $title = "项目 | Mr.Designer";
            break;
            case "service":
                $title = "服务 | Mr.Designer";
            break;
            case "process":
                $title = "流程 | Mr.Designer";
            break;
            case "contact":
                $title = "联系 | Mr.Designer";
            break;
        }
?>
<!doctype html>
<html lang="zh-cn">
    <head>
        <meta charset="utf-8">
        <title><?php echo $title;?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/socicon.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/iconsmind.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/interface-icons.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/owl.carousel.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/lightbox.min.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/theme.css" rel="stylesheet" type="text/css" media="all" />
    </head>
    <body class="scroll-assist" data-reveal-selectors="section:not(.masonry):not(:first-of-type):not(.parallax)" data-reveal-timing="1000">
        <a id="top"></a>
        <div class="loader"></div>
        <nav>
            <div class="nav-bar nav--absolute nav--transparent" data-fixed-at="200">
                <div class="nav-module logo-module left">
                    <a href="index.php">
                        <img class="logo logo-dark" alt="logo" src="img/logo-dark.png" />
                        <img class="logo logo-light" alt="logo" src="img/logo-light.png" />
                    </a>
                </div>
                <div class="nav-module menu-module left">
                    <ul class="menu">
                        <li>
                            <a href="index.php">
                                首页
                            </a>
                        </li>
                        <li>
                            <a href="about.php">
                                公司
                            </a>
                        </li>
                        <li>
                            <a href="project.php">
                                项目
                            </a>
                            <ul class="multi-column hidden">
                                <li>
                                    <ul>
                                        <li>
                                            <span class="multi-column__title">
                                                产品开发
                                            </span>
                                        </li>
                                        <li>
                                            <a href="#">
                                                吃货盒子
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                白禾订购
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                JUJU
                                            </a>
                                        </li>
                                        <li>
                                        </li>
                                        <li>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <ul>
                                        <li>
                                            <span class="multi-column__title">
                                                设计作品
                                            </span>
                                        </li>
                                        <li>
                                            <a href="#">
                                                包装设计
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                VI设计
                                            </a>
                                        </li>
                                        <li>
                                        </li>
                                        <li>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <ul>
                                        <li>
                                            <span class="multi-column__title">
                                                摄影作品
                                            </span>
                                        </li>
                                        <li>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="service.php">
                                服务
                            </a>
                        </li>
                        <li>
                            <a href="process.php">
                                流程
                            </a>
                        </li>
                        <li>
                            <a href="contact.php">
                                联系
                            </a>
                        </li>
                    </ul>
                </div>
                <!--end nav module-->
               
                <div class="nav-module right" style="padding-right: 20px;">
                    <a href="#" class="nav-function modal-trigger" data-modal-id="search-form">
                        <i class="interface-search icon icon--sm"></i>
                        <span>Search</span>
                    </a>
                </div>
            </div>
            <!--end nav bar-->
            <div class="nav-mobile-toggle visible-sm visible-xs">
                <i class="icon-Align-Right icon icon--sm"></i>
            </div>
        </nav>
        <!--end of modal-container-->