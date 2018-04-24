<?php include('header.php');?>

        <div class="main-container">
            <section class="height-70 bg--dark imagebg page-title page-title--animate parallax" data-overlay="6">
                <div class="background-image-holder">
                    <img alt="image" src="img/hero15.jpg" />
                </div>
                <div class="container pos-vertical-center">
                    <div class="row">
                        <div class="col-sm-10 col-sm-offset-1 text-center">
                            <h3>让我们做一些伟大的事</h3>
                            <p class="lead">阐述您的产品需求和概念，我们会根据您的想法给出最完善的解决方案。</p>
                        </div>
                    </div>
                    <!--end row-->
                </div>
                <!--end container-->
            </section>
            <section class="features features-10">
                <div class="feature bg--white col-md-4 text-center">
                    <i class="icon icon--lg icon-Map-Marker2"></i>
                    <h4>工作场地</h4>
                    <p>
                        浙江杭州
                        <br /> 滨江区滨康路
                        <br /> 669号远方光电2幢220室
                    </p>
                </div>
                <div class="feature bg--secondary col-md-4 text-center">
                    <i class="icon icon--lg icon-Phone-2"></i>
                    <h4>联系电话</h4>
                    <p>Tel: 18857707618
                    </p>
                </div>
                <div class="feature bg--dark col-md-4 text-center">
                    <i class="icon icon--lg icon-Computer"></i>
                    <h4>在线联系</h4>
                    <p>
                        Email:
                        <a href="mailto:zaowuren@gmail.com">cj951236@aliyun.com</a>
                    </p>
                </div>
            </section>
            <section>
                <div class="container">
                <div class="row">
                    <div class="col-sm-6">

                        <form action="sendmail.php" method="post">
                            <p>您的名字：<input type="text" name="name" /></p>
                        	<p>邮箱地址：<input type="text" name="mail" /></p>
                        	<p>手机号码：<input type="text" name="number" /></p>
                        	<p>您的留言：<textarea name="content" cols="50" rows="5"></textarea></p>
                        	<p><input type="submit" value="提交"  /></p>
                        </form>

                         </div>
                     </div>
                    <!--end of row-->
                </div>
                <!--end of container-->
            </section>

<?php include('footer.php');?>