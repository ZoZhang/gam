<?php
/**
 * Created by PhpStorm.
 * User: antony.cuchet
 * Date: 03/01/2019
 * Time: 11:39
 */
/**
 * Projet Goask Me
 *
 * @author ZHANG Zhao <zo.zhang@gmail.com>
 * @demo http://gam.zhaozhang.fr
 */
namespace Gam\Model;


class Search extends Contract {

    /**
     * search contract
     * @param array $paramete
     * @return array|void
     */
    public function getContract($paramete = [], $fields = [])
    {
        $data = [];
        $where = '';
        $parametes = [];

        if (count($paramete)) {
            foreach($paramete as $key => $val) {

                if ($where) {
                    $where .= ' OR ';
                }

                $where .= sprintf(' %s LIKE %s%s%s ', $key,'\'%', $val, '%\'');
            }
        } else {
            $where = ' 1 ';
        }

        $_querys = [
            'table' => 'contract',
            'fields' => !count($fields) ? '*' : $fields,
            'where' => $where,
            'order' => 'created_at desc',
        ];

        $data = $this->getData($_querys);

        return $data;
    }


}