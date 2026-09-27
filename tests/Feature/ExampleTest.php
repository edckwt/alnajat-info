<?php

// الصفحة الرئيسية تعمل على قاعدة فارغة (بلا إعدادات ولا أخبار).
it('opens the home page on an empty database', function () {
    $this->get('/')->assertOk();
});
