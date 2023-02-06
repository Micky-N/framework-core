<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <style type="text/css" media="all">
        /* Base ------------------------------ */
        @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");

        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            text-align: justify;
            -webkit-text-size-adjust: none;
        }

        a {
            color: #3869D4;
        }

        a img {
            border: none;
        }

        td {
            word-break: break-word;
        }

        /* Type ------------------------------ */
        body,
        td,
        th {
            font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
        }

        h1 {
            margin-top: 0;
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
        }

        h2 {
            margin-top: 0;
            color: #333333;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }

        h3 {
            margin-top: 0;
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }

        td,
        th {
            font-size: 16px;
        }

        p,
        ul,
        ol,
        blockquote {
            margin: .4em 0 1.1875em;
            font-size: 16px;
            line-height: 1.625;
        }

        p.sub {
            font-size: 13px;
        }

        .align-center {
            text-align: center;
        }

        /* Buttons ------------------------------ */
        .button {
            display: inline-block;
            color: #FFF;
            text-decoration: none;
            border-radius: 3px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
            -webkit-text-size-adjust: none;
            box-sizing: border-box;
        }

        .button--blue {
            background-color: #3869D4;
            border-top: 10px solid #3869D4;
            border-right: 18px solid #3869D4;
            border-bottom: 10px solid #3869D4;
            border-left: 18px solid #3869D4;
        }

        .button--green {
            background-color: #22BC66;
            border-top: 10px solid #22BC66;
            border-right: 18px solid #22BC66;
            border-bottom: 10px solid #22BC66;
            border-left: 18px solid #22BC66;
        }

        .button--red {
            background-color: #FF6136;
            border-top: 10px solid #FF6136;
            border-right: 18px solid #FF6136;
            border-bottom: 10px solid #FF6136;
            border-left: 18px solid #FF6136;
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                text-align: center !important;
            }
        }

        /* Discount Code ------------------------------ */

        .discount {
            width: 100%;
            margin: 24px 0;
            padding: 24px;
            background-color: #F4F4F7;
            border: 2px dashed #CBCCCF;
        }

        .discount_heading {
            text-align: center;
        }

        .discount_body {
            text-align: center;
            font-size: 15px;
        }

        /* Attribute list ------------------------------ */
        .attributes {
            margin: 0 0 21px;
            width: 100%;
        }

        .attributes_content {
            background-color: #F4F4F7;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }

        .social td {
            padding: 0;
            width: auto;
        }

        .purchase_heading p {
            margin: 0;
            color: #85878E;
            font-size: 12px;
        }

        body {
            background-color: #F2F4F6;
            color: #51545E;
        }

        p {
            color: #51545E;
        }

        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #F2F4F6;
        }

        .email-content {
            width: 100%;
            margin: 0;
            padding: 5vh 0 0 0;
        }

        /* Body ------------------------------ */
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .email-body_inner,
        .email-footer {
            width: 700px;
            margin: 0 auto;
            padding: 0;

        }

        .email-body_inner {
            background-color: #FFFFFF;
        }

        .email-footer {
            text-align: center;
        }

        .email-footer p {
            color: #A8AAAF;
        }

        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            text-align: center;
        }

        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EAEAEC;
            width: 100%;
        }

        .content-cell {
            padding: 45px;
        }

        /*Media Queries ------------------------------ */
        @media only screen and (max-width: 600px) {
            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }

        @media (prefers-color-scheme: dark) {
            body,
            .email-body,
            .email-body_inner,
            .email-content,
            .email-wrapper,
            .email-footer {
                background-color: #333333 !important;
                color: #FFF !important;
            }

            p,
            ul,
            ol,
            blockquote,
            h1,
            h2,
            h3,
            span {
                color: #FFF !important;
            }

            .attributes_content {
                background-color: #222 !important;
            }
        }
    </style>
</head>
<body>
<table class="email-wrapper">
    <tr>
        <td>
            <table class="email-content">
                <!-- Email Body -->
                <tr>
                    <td class="email-body">
                        <table class="email-body_inner">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell">
                                    <div>
                                        <?= $this->escape($greeting) ?>
                                        <?php foreach ($contents as $content): ?>
                                            <?= $this->escape($content) ?>
                                        <?php endforeach ?>
                                        <?= $this->escape($footer) ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?= $this->escape($signature) ?>
            </table>
        </td>
    </tr>
</table>
</body>
</html>