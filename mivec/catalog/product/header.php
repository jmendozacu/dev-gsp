<link href="<?php echo __MIVEC_URL__?>res/css/styles.css" rel="stylesheet" />
<!--link href="/skin/frontend/default/ma_sagitta/css/styles_red.css" rel="stylesheet"-->
<!--script type="text/javascript" src="/js/mivec/jquery-1.4.2.min.js"></script-->
<script type="text/javascript" src="<?php echo __MIVEC_URL__?>res/js/jquery/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="<?php echo __MIVEC_URL__?>res/js/mivec/common.js"></script>
<style>
    body{padding:0;margin:10px 100px 0 100px; text-align:left;font-family:"Open Sans";}
    a {color:#39C}
    a:hover{text-decoration:underline}

    .top td{padding:5px 5px 2px 5px;}
    .top a,.list_row a {padding-right:5px;text-decoration:none}
    .top a:hover{color:#fff;background:#099; text-decoration:underline}

    /* CSS Document */
    table {font-size:14px;}
    /* word-break:break-all; word-wrap:break-word; */
    .list_row {margin:5px 0;}
    /*.list_row td {padding:2px;}*/
    .list_row td {padding:5px 5px 2px 5px;}
    .list_row a{ text-decoration:none}
    .list_row a:hover{color:#069}

    .break {word-break:break-all; word-wrap:break-word;}

    .important {font-size:16px;font-weight:bold;color:#F00;display:block}

    select {
        margin: 2px;
        padding: 3px;
        text-overflow: ellipsis;
    }
    input.input-text, textarea {
        padding: 5px;
        text-overflow: ellipsis;
    }

    .notice-msg {
        background-position:25px center !important;
        background-repeat:no-repeat !important;
        padding:10px 20px 10px 80px !important;
        font-size:12px !important;
    }

    pager .amount, .pager .limiter, .pager .pages, .sorter .amount, .sorter .limiter, .sorter .view-mode, .sorter .sort-by {
        padding-bottom: 5px;
        padding-top: 5px;
    }
    .pager .pages {
        float: right;
        vertical-align: middle;
    }
    caption, th, td {
        font-weight: normal;
        text-align: left;
        vertical-align: top;
    }
</style>
<?php
$navi = array(
    'Product'   => array(
        'list.php'          => '产品列表' ,
        //'review.php'      => '产品评论' ,
        //'stock.php'         => '库存列表' ,
        //'stock.update.php'  => '库存更新'
    ),
);
?>
<table width="100%" border="0" cellspacing="1" cellpadding="0" class="top" style="margin-bottom:10px;background:#e8e8e8">
    <?php foreach ($navi as $_naviHeader    => $_naviVal):?>
    <tr>
        <td width="8%" height="22" bgcolor="#FFFFFF"><?php echo $_naviHeader?></td>
        <td width="92%" bgcolor="#FFFFFF">
            <?php foreach ($_naviVal as $_url   => $_title):?>
                <a href="<?php echo __MIVEC_URL__ . strtolower($_naviHeader) ."/" . $_url?>"><?php echo $_title?></a>
            <?php endforeach;?>
        </td>
    </tr>
    <?php endforeach;?>
</table>
