<?php

class notifyletter
{

    public function __construct()
    {
        global $control;
        $this->notifyletter();
    }

    private function notifyletter()
    {
        global $control;
        $array_products = array();
        $array_variants = array();
        $array_notify_products_del = array();
        $array_notify_variants_del = array();
        $sql_class = new sql();

        $sql = "select * from it_b_notify where type='1' GROUP BY product_id";
        $data_query = $sql_class->query($sql);
        while ($row = $sql_class->fetch_assoc($data_query)) {
            $array_products["id"] = $row["product_id"];
        }

        $sql = "select * from it_b_notify where type='2' GROUP BY product_id";
        $data_query = $sql_class->query($sql);
        while ($row = $sql_class->fetch_assoc($data_query)) {
            $array_variants["id"] = $row["product_id"];
        }

        $sitename = $to_email->sitename = $control->settings->sitename;

        $catalogUrl = all::getUrl(40);
        $letter_data = all::b_data_all(18, "sitetext");
        $to_email->theme = $letter_data->name;
        $to_email->text = nl2br($letter_data->text);


        $sql = "select * from it_b_catitem where ballance is not NULL and id in ('" . implode("', '", $array_products) . "')";
        $data_query = $sql_class->query($sql);
        if ($sql_class->num_rows($data_query) > 0) {
            while ($row = $sql_class->fetch_assoc($data_query)) {
                if ($row["ballance"] == "") {
                    continue;
                }
                $list_notyfi = new Listing("notify", "blocks", "all", " type='1' AND product_id='" . $row['id'] . "' AND ");
                $list_notyfi->on = "false";
                $list_notyfi->getList();
                $list_notyfi->getItem();
                $list_notyfi_1 = $list_notyfi->item;
                if (count($list_notyfi_1)) {
                    foreach ($list_notyfi_1 as $value) {
                        $array_notify_products_del[] = $value->id;
                        $notify_data = all::b_data_all($value->id, "notify");
                        if (!empty($notify_data->user)) {
                            $user_data = all::b_data_all($notify_data->user, "user");
                            $notify_data->email = $user_data->email;
                            $notify_data->name = $user_data->name;
                        }
                        $product_data = all::b_data_all($value->product_id, "catitem");
                        $url = $catalogUrl . "_aview_b" . $product_data->id . "/";
                        $email_data = clone($to_email);
                        $email_data->email = $notify_data->email;
                        $email_data->text = str_replace("{name}", $notify_data->name, $email_data->text);
                        $email_data->text = str_replace("{product}", ("<a href='" . $_SERVER["HTTP_HOST"] . $url . "'>" . $product_data->name . "</a>"), $email_data->text);

                        $email_data->html = sprintt($email_data, 'mailtemplates/touser/notify.html');
                        all::send_mail($notify_data->email, $email_data->theme, $email_data->html, false, false, "$sitename");
                    }

                    $sql = "DELETE FROM it_b_notify where id in ('" . implode("', '", $array_notify_products_del) . "')";
                    $sql_class->query($sql);
                }
            }
        }

        $sql = "select * from it_b_variant where ballance is not NULL and id in ('" . implode("', '", $array_variants) . "')";
        $data_query = $sql_class->query($sql);
        if ($sql_class->num_rows($data_query) > 0) {
            while ($row = $sql_class->fetch_assoc($data_query)) {
                if ($row["ballance"] == "") {
                    continue;
                }
                $list_notyfi = new Listing("notify", "blocks", "all", " type='2' AND product_id='" . $row['id'] . "' AND ");
                $list_notyfi->on = "false";
                $list_notyfi->getList();
                $list_notyfi->getItem();
                $list_notyfi_2 = $list_notyfi->item;
                if (count($list_notyfi_2)) {
                    foreach ($list_notyfi_2 as $value) {
                        $array_notify_variants_del[] = $value->id;
                        $notify_data = all::b_data_all($value->id, "notify");
                        if (!empty($notify_data->user)) {
                            $user_data = all::b_data_all($notify_data->user, "user");
                            $notify_data->email = $user_data->email;
                            $notify_data->name = $user_data->name;
                        }
                        $product_data = all::b_data_all($value->product_id, "variant");
                        $product_parent_data = all::b_data_all($product_data->blockparent, "catitem");
                        $url = $catalogUrl . "_aview_b" . $product_data->blockparent . "/";
                        $email_data = clone($to_email);
                        $email_data->email = $notify_data->email;
                        $email_data->text = str_replace("{name}", $notify_data->name, $email_data->text);
                        $email_data->text = str_replace("{product}", ("<a href='" . $_SERVER["HTTP_HOST"] . $url . "'>" . $product_parent_data->name . " (" . $product_data->name . ")" . "</a>"), $email_data->text);

                        $email_data->html = sprintt($email_data, 'mailtemplates/touser/notify.html');
                        all::send_mail($notify_data->email, $email_data->theme, $email_data->html, false, false, "$sitename");
                    }

                    $sql = "DELETE FROM it_b_notify where id in ('" . implode("', '", $array_notify_variants_del) . "')";
                    $sql_class->query($sql);
                }
            }
        }
        die();
    }
}