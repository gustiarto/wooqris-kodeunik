<?php
/**
 * Class untuk menangani model Payment
 */
class QRIS_Payment {
    private $id;
    private $order_id;
    private $unique_amount;
    private $created_at;
    private $status;
    private $attempts;
    private $last_check;

    /**
     * Constructor
     */
    public function __construct($data = array()) {
        $this->id = isset($data['id']) ? $data['id'] : 0;
        $this->order_id = isset($data['order_id']) ? $data['order_id'] : 0;
        $this->unique_amount = isset($data['unique_amount']) ? $data['unique_amount'] : 0.00;
        $this->created_at = isset($data['created_at']) ? $data['created_at'] : current_time('mysql');
        $this->status = isset($data['status']) ? $data['status'] : 'pending';
        $this->attempts = isset($data['attempts']) ? $data['attempts'] : 0;
        $this->last_check = isset($data['last_check']) ? $data['last_check'] : null;
    }

    /**
     * Cari pembayaran berdasarkan nominal
     */
    public static function find_by_amount($amount) {
        global $wpdb;
        $table = $wpdb->prefix . QRIS_TABLE_NAME;
        
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE unique_amount = %f",
                $amount
            ),
            ARRAY_A
        );

        return $row ? new self($row) : null;
    }

    /**
     * Simpan pembayaran baru
     */
    public function save() {
        global $wpdb;
        $table = $wpdb->prefix . QRIS_TABLE_NAME;

        $data = array(
            'order_id' => $this->order_id,
            'unique_amount' => $this->unique_amount,
            'created_at' => $this->created_at,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'last_check' => $this->last_check
        );

        $format = array(
            '%d',  // order_id
            '%f',  // unique_amount
            '%s',  // created_at
            '%s',  // status
            '%d',  // attempts
            '%s'   // last_check
        );

        if ($this->id) {
            $wpdb->update(
                $table,
                $data,
                array('id' => $this->id),
                $format,
                array('%d')
            );
        } else {
            $wpdb->insert($table, $data, $format);
            $this->id = $wpdb->insert_id;
        }

        return $this->id;
    }

    /**
     * Hapus pembayaran
     */
    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . QRIS_TABLE_NAME;

        return $wpdb->delete(
            $table,
            array('id' => $this->id),
            array('%d')
        );
    }

    /**
     * Get/Set methods
     */
    public function get_id() {
        return $this->id;
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function get_unique_amount() {
        return $this->unique_amount;
    }

    public function get_status() {
        return $this->status;
    }

    public function set_status($status) {
        $this->status = $status;
    }

    public function increment_attempts() {
        $this->attempts++;
        $this->last_check = current_time('mysql');
    }

    public function get_attempts() {
        return $this->attempts;
    }

    public function get_created_at() {
        return $this->created_at;
    }
}
