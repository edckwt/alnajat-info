@php($whatsapp = \App\Models\Setting::get('subscribe_whasapp'))
@php($email = \App\Models\Setting::get('subscribe_email'))
@if (filled($whatsapp) || filled($email))
    <div class="container">
        <div class="row">
            <div class="col-6 whatsapp">
                <a href="{{ $whatsapp }}"><i class="fab fa-whatsapp"></i><br>للاشتراك في النشرة الإخبارية عبر واتساب اضغط هنا</a>
            </div>
            <div class="col-6 email">
                <a data-toggle="modal" data-target="#subscribe_emailModal" href="{{ $email }}"><i class="fa fa-envelope"></i><br>للاشتراك في النشرة الإخبارية عبر البريد اضغط هنا</a>
                <div class="modal fade" id="subscribe_emailModal" tabindex="-1" role="dialog" aria-labelledby="subscribe_emailLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="subscribe_emailLabel">اشتراك</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <form action="https://alnajat.us4.list-manage.com/subscribe/post?u=e491488d5ec7ea051798a5ec4&amp;id=a5f6d12767" method="post" target="_blank" novalidate>
                                    <div class="form-group">
                                        <label for="subscribe_email_input" class="site_unsubscribe">الاشتراك بالنشرة البريدية</label>
                                        <input type="email" class="form-control" id="subscribe_email_input" name="EMAIL" placeholder="البريد الإلكتروني" required>
                                        <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_e491488d5ec7ea051798a5ec4_a5f6d12767" tabindex="-1" value=""></div>
                                    </div>
                                    <input type="submit" value="اشتراك" name="subscribe" class="btn btn-primary">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p>في حال عدم رغبتكم في تلقي النشرة الإخبارية عبر الواتساب او البريد الإلكتروني يرجى <a href="{{ \App\Models\Setting::get('unsubscribe') }}">الضغط هنا</a> لإلغاء الإشتراك</p>
    </div>
@endif
