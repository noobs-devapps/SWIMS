<?php

    class swimsdb extends CI_Model {

        public function __construct() {
            $this->load->database();
        }

        public function get_users() {
            return $this->db->from("users")->get()->result_array();
        }

        public function validate($username, $password) {		//changed to hash and salt
            $result = $this -> db -> from("users") -> where("username", $username) -> get();
            if (!$result) {
                return false;
            }

            $user = $result -> result_array()[0];

            $salt = $user['salt'];
            $db_hash = $user['password'];
            $login_hash = hash(self ::$hash_algorithm, $password . $salt);


            if ($login_hash==$db_hash) {
                return $this->db->from("users")->where("username",$username)->get()->result_array();
            }
            return false;
        }

        public function add_user($username, $firstname,$lastname,$email,$position,$posno,$password) {
            $salt = self ::create_salt();				//changed to hash and salt
            $hash = hash(self ::$hash_algorithm, $password . $salt);


            $data = array(
                "username" => $username,
                "first_name" => $firstname,
                "last_name" => $lastname,
                "email_address" => $email,
                "position" => $position,
                "position_no" => $posno,
                "password" => $hash,
                "salt" => $salt
            );

            return $this->db->insert("users", $data);
        }

        public function delete_user($user) {

            $this->db->where("username", $user);
            return $this->db->delete("users");
        }

        public function edit_user($username, $firstname,$lastname,$email,$position,$posno,$password) {
            $this->db->where("username", $username);			//changed to hash and salt
            $salt = self ::create_salt();
            $hash = hash(self ::$hash_algorithm, $password . $salt);


            $data = array(
                "username" => $username,
                "first_name" => $firstname,
                "last_name" => $lastname,
                "email_address" => $email,
                "position" => $position,
                "position_no" => $posno,
                "password" => $hash,
                "salt" => $salt
            );
            return $this->db->update("users", $data);
        }

        public function display_products() {
            $sql = "SELECT *
                         
                    FROM products p JOIN    supplies sup
                                      ON    p.product_id = sup.product_id
                                    JOIN    suppliers s
                                      ON    sup.supplier_id = s.supplier_id
                                    JOIN    product_warehouse pw
                                      ON    p.product_id = pw.product_id
                                    JOIN    warehouses w
                                      ON    pw.warehouse_id = w.warehouse_id
                                    JOIN    categories c
                                      ON    p.category_no = c.category_id
                                    JOIN    package_items pi
                                      ON    p.product_id = pi.product_id
                                    JOIN    packages pk
                                      ON    pi.package_id = pk.package_id
                    ORDER BY  pw.warehouse_id";

            return $this->db->query($sql)->result_array();
        }

        public function get_products_order($order) {
            return $this->db->from("order_details")
                ->where("order_no",$order)
                ->get()->result_array();
        }

        public function get_categories() {
            return $this->db->from("categories")->get()->result_array();
        }

        public function get_packages(){
            return $this->db->from("packages")->get()->result_array();
        }

        public function get_suppliers(){
            return $this->db->from("suppliers")->get()->result_array();
        }

        public function get_warehouses(){
            return $this->db->from("warehouses")->get()->result_array();
        }

        public function add_product($data) {
            $this->db->insert("products", $data);

        }

        public function get_last_product(){
            $query = "SELECT    MAX(product_id) as 'lastproduct'
                      FROM      products";

            return $this->db->query($query)->result_array();
        }

        public function get_last_order() {
            $query = "select max(order_no) as 'lastorder' from orders";

            return $this->db->query($query)->result_array();
        }

        public function add_product_to($product, $warehouse, $package, $supplier) {

            $product_warehouse = array(
                "product_id" => $product,
                "warehouse_id" => $warehouse
            );

            $supply = array(
                "product_id" => $product,
                "supplier_id" => $supplier
            );

            $package_item = array(
                "product_id" => $product,
                "package_id" => $package
            );

            $this->db->insert("product_warehouse", $product_warehouse);
            $this->db->insert("supplies", $supply);
            $this->db->insert("package_items", $package_item);
        }

        public function get_individual_products(){
            $sql ="select * from products p join package_items pi on p.product_id=pi.product_id
                   join categories c on p.category_no = c.category_id
                   where pi.package_id = 0 order by c.category_name";

            return $this->db->query($sql)->result_array();
        }

        public function add_order($order) {

            return $this->db->insert("orders", $order);
        }

        public function get_orders(){

            return $this->db->from("orders")->get()->result_array();
        }

        private static function create_salt() {		//changed for hash and salt
            $text = md5(uniqid(rand(), TRUE));
            return substr($text, 0, 3);
        }

        private static $hash_algorithm = 'sha256';	//changed for hash and salt

        public function add_order_details($details) {
            return $this->db->insert("order_details", $details);
        }

        public function get_full_orders() {
            $query = "select * from orders o join order_details od
                      on o.order_no = od.order_no join products p on od.product_id
                      = p.product_id";

            return $this->db->query($query)->result_array();
        }

        public function get_full_order($order) {
            $query = "select * from orders o 
                      join order_details od on o.order_no = od.order_no 
                      join products p on od.product_id = p.product_id 
                      join product_warehouse pw on p.product_id = pw.product_id 
                      where o.order_no = '$order'";

            return $this->db->query($query)->result_array();
        }

        public function pull_out_order($order, $wh) {
            $query = "select * from orders o join order_details od
						 on o.order_no = od.order_no 
					   join products p 
					     on od.product_id = p.product_id 
					   join product_warehouse pw
                         on p.product_id = pw.product_id 
					   join warehouses w
                         on pw.warehouse_id = w.warehouse_id
		             where o.order_no = '$order'
                     and   pw.warehouse_id = '$wh'";

            return $this->db->query($query)->result_array();
        }

        public function add_pullout($data) {
            return $this->db->insert("pull_out_slips", $data);
        }

        public function get_pullouts() {
            return $this->db->from("pull_out_slips")->get()->result_array();
        }

        public function update_status($status, $order) {
            $query = "update orders set status='$status' where order_no ='$order'";

            return $this->db->query($query);
        }

        public function update_inventory($new_quantity, $product) {
            $query = "update products set quantity_in_stock ='$new_quantity'
                      where product_id ='$product'";

            return $this->db->query($query);
        }

        public function get_product($product) {
            return $this->db->from("products")
                ->where("product_id",$product)
                ->get()->result_array();
        }

        public function check_POS($order) {
            return $this->db->from("pull_out_slips")
                ->where("order_no", $order)->get()
                ->result_array();
        }

        public function deliver($drno, $date, $order) {
            $query = "UPDATE orders 
                      SET delivery_receipt_no ='$drno',
                          shipped_date='$date',
                          status='On delivery'
                      WHERE order_no = '$order'";

            return $this->db->query($query);
        }

        public function get_ORs() {
            return $this->db->from("official_receipts")
                ->get()->result_array();
        }

        public function receive_payment($data, $order) {
            $this->db->insert("official_receipts", $data);
            $query ="UPDATE orders
                     SET    status ='Completed'
                     WHERE  order_no ='$order'";

            $this->db->query($query);
        }
    }
?>