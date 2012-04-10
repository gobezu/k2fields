<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * NestedSetDbTable
 *
 * Copyright (C) 2009 Nikola Posa (http://www.nikolaposa.in.rs)
 *
 * This file is part of NestedSetDbTable.
 *
 * NestedSetDbTable is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NestedSetDbTable is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NestedSetDbTable. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Abstract class that provides API for managing Nested set 
 * database table.
 *
 * @author Nikola Posa <posa.nikola@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License
 */
abstract class NestedSetDbTable_Abstract {
        const FIRST_CHILD = 'firstChild';
        const LAST_CHILD = 'lastChild';
        const NEXT_SIBLING = 'nextSibling';
        const PREV_SIBLING = 'prevSibling';

        /**
         * Database adapter instance, that will be used for
         * communication with the database.
         *
         * @var PDO
         */
        protected $_dbAdapter;
        /**
         * Default NestedSetDbTable_DbAdapter_Interface instance.
         * 
         * @var PDO
         */
        protected static $_defaultDbAdapter;
        /**
         * The table name.
         *
         * Must be overriden by the extending class.
         *
         * @var string
         */
        protected $_name;
        /**
         * Name of the primary key column.
         *
         * Must be overriden by the extending class.
         *
         * @var string
         */
        protected $_primary;
        /**
         * Left column name in nested table.
         *
         * Must be overriden by the extending class.
         *
         * @var string
         */
        protected $_left;
        /**
         * Right column name in nested table.
         *
         * Must be overriden by the extending class.
         *
         * @var string
         */
        protected $_right;

        /**
         * Constructor.
         * 
         * @param PDO $dbAdapter
         * @return void
         */
        public function __construct(PDO $dbAdapter = null) {
                if (!$this->_name) {
                        require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                        throw new NestedSetDbTable_Exception('You must supply name of your table in database.');
                } elseif (!$this->_primary) {
                        require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                        throw new NestedSetDbTable_Exception('You must supply primary key column name.');
                } elseif (!$this->_left || !$this->_right) {
                        require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                        throw new NestedSetDbTable_Exception('Both "left" and "right" column names must be supplied.');
                }

                if ($dbAdapter) {
                        $this->_setDbAdapter($dbAdapter);
                }
                $this->_setupDatabaseAdapter();
        }

        /**
         * Initializing database adapter.
         *
         * @return void
         */
        protected function _setupDatabaseAdapter() {
                if (!$this->_dbAdapter) {
                        $this->_dbAdapter = self::getDefaultAdapter();

                        if (!$this->_dbAdapter instanceof PDO) {
                                require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                                throw new NestedSetDbTable_Exception('No adapter found for ' . get_class($this));
                        }
                }

                //always use exceptions
                $this->_dbAdapter->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //Because we are using double qoutes for the identifiers
                switch ($this->_dbAdapter->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                        case 'mysql':
                                $this->_dbAdapter->query("SET sql_mode='ANSI_QUOTES'");
                                break;
                        case 'mssql': case 'sybase': case 'dblib':
                                $this->_dbAdapter->query("SET QUOTED_IDENTIFIER ON");
                                break;
                }
        }

        /**
         * Sets database adapter.
         *
         * @param PDO $dbAdapter
         * @return void
         */
        protected function _setDbAdapter(PDO $dbAdapter) {
                $this->_dbAdapter = $dbAdapter;
        }

        /**
         * Gets database adapter.
         *
         * @return PDO
         */
        public function getAdapter() {
                return $this->_dbAdapter;
        }

        /**
         * Sets the default database adapter.
         *
         * @param PDO|null $dbAdapter
         * @return void
         */
        public static function setDefaultAdapter(PDO $dbAdapter = null) {
                self::$_defaultDbAdapter = $dbAdapter;
        }

        /**
         * Gets the default database adapter.
         *
         * @return PDO|null
         */
        public static function getDefaultAdapter() {
                return self::$_defaultDbAdapter;
        }

        /**
         * Quotes identifier.
         *
         * @param string $identifier
         * @return string
         */
        protected function _quoteIdentifier($identifier) {
                return '"' . $identifier . '"';
        }

        /**
         * Gets whole tree, including depth information.
         *
         * @param string An SQL WHERE clause.
         * @return array
         */
        public function getTree($where = null, $cols = 'node.*') {
                $where = (string) $where;

                if (strlen($where) > 0) {
                        $where = ' AND ' . $where;
                }

                $name = $this->_quoteIdentifier($this->_name);
                $primary = $this->_quoteIdentifier($this->_primary);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);
                
                if (is_array($cols)) $cols = implode(', ', $cols);

                $sql = 'SELECT '.$cols.'
        FROM ' . $name . ' AS node , ' . $name . ' AS parent
        WHERE node.' . $left . ' BETWEEN parent.' . $left . ' AND parent.' . $right . $where . '
        GROUP BY node.' . $primary . '
        ORDER BY node.' . $left;

                $stmt = $this->_dbAdapter->prepare($sql);
                
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * Method for adding new node.
         *
         * @param array $data
         * @param int|null $objectiveNodeId
         * @param string $position Position regarding on objective node.
         * @return int The number of affected rows.
         */
        public function insert($data, $objectiveNodeId = null, $position = self::LAST_CHILD) {
                if (!$this->_checkNodePosition($position)) {
                        require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                        throw new NestedSetDbTable_Exception('Invalid node position is supplied.');
                }

                $data = array_merge($data, $this->_getLftRgt($objectiveNodeId, $position));
                $cols = array_keys($data);
                
                $vals = array();
                foreach ($cols as $i => $col) {
                        $cols[$i] = $this->_quoteIdentifier($col);
                        $vals[] = '?';
                }

                $sql = 'INSERT INTO '
                        . $this->_quoteIdentifier($this->_name)
                        . ' (' . implode(', ', $cols) . ')
            VALUES (' . implode(', ', $vals) . ')';

                $stmt = $this->_dbAdapter->prepare($sql);
                $stmt->execute(array_values($data));

                return $stmt->rowCount();
        }

        /**
         * Updates info of some node.
         *
         * @param array $data
         * @param int $id Id of a node that is being updated.
         * @param int|null $objectiveNodeId
         * @param string $position Position regarding on objective node.
         * @return int The number of affected rows.
         */
        public function updateNode($data, $id, $objectiveNodeId, $position = self::LAST_CHILD) {
                $id = (int) $id;
                $objectiveNodeId = (int) $objectiveNodeId;

                if (!$this->_checkNodePosition($position)) {
                        require_once(dirname(__FILE__) . '/NestedSetDbTable/Exception.php');
                        throw new NestedSetDbTable_Exception('Invalid node position is supplied.');
                }

                //Only if the objective id differs.
                if ($objectiveNodeId != $this->_getCurrentObjectiveId($id, $position)) {
                        $this->_reduceWidth($id);

                        $data = array_merge($data, $this->_getLftRgt($objectiveNodeId, $position, $id));
                }

                $set = array();
                foreach ($data as $col => $val) {
                        $set[] = $this->_quoteIdentifier($col) . ' = ?';
                }

                $retval = 0;
                if (!empty($set)) { //Has some data to update?
                        $name = $this->_quoteIdentifier($this->_name);
                        $primary = $this->_quoteIdentifier($this->_primary);
                        $where = $primary . ' = ' . $this->_dbAdapter->quote($id, PDO::PARAM_INT);

                        $stmt = $this->_dbAdapter->prepare(
                                        'UPDATE '
                                        . $name
                                        . 'SET ' . implode(', ', $set) . '
                WHERE ' . $where);
                        $stmt->execute(array_values($data));

                        $retval = $stmt->rowCount();
                }

                return $retval;
        }

        /**
         * Checks whether valid node position is supplied.
         *
         * @param string $position Position regarding on objective node.
         * @return bool
         */
        private function _checkNodePosition($position) {
                $r = new ReflectionClass($this);

                if (!in_array($position, $r->getConstants())) {
                        return false;
                }

                return true;
        }

        /**
         * Deletes some node.
         *
         * @param mixed $id Id of a node.
         * @param bool $cascade Whether to delete all child nodes.
         * @return int The number of affected rows.
         */
        public function deleteNode($id, $cascade = false) {
                $id = (int) $id;
                $name = $this->_quoteIdentifier($this->_name);
                $primary = $this->_quoteIdentifier($this->_primary);

                if ($cascade == false) {
                        $this->_reduceWidth($id);

                        //Deleting node.
                        $stmt = $this->_dbAdapter->prepare(
                                        'DELETE FROM  '
                                        . $name . '
            WHERE ' . $primary . ' = ?');
                        $stmt->bindParam(1, $id, PDO::PARAM_INT);
                        $stmt->execute();

                        return $stmt->rowCount();
                } else {
                        $retval = 0;
                        $leftCol = $this->_quoteIdentifier($this->_left);
                        $rightCol = $this->_quoteIdentifier($this->_right);

                        $sql = 'SELECT ' . $leftCol . ',' . $rightCol . ',' . '(' . $rightCol . ' - ' . $leftCol . ' + 1) AS "width" FROM ' . $name . ' WHERE ' . $primary . ' = ?';
                        $stmt = $this->_dbAdapter->prepare($sql);
                        $stmt->bindParam(1, $id, PDO::PARAM_INT);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $lft = $result[$this->_left];
                                $rgt = $result[$this->_right];
                                $width = $result['width'];

                                //Deleting items.
                                $stmt = $this->_dbAdapter->prepare(
                                                'DELETE FROM  '
                                                . $name . '
                WHERE ' . $leftCol . 'BETWEEN ' . $lft . ' AND ' . $rgt);
                                $stmt->execute();
                                $retval += $stmt->rowCount();

                                $stmt = $this->_dbAdapter->prepare(
                                                'UPDATE '
                                                . $name
                                                . 'SET ' . $leftCol . ' = ' . $leftCol . '-' . $width . ' WHERE ' . $leftCol . '>' . $lft);
                                $stmt->execute();
                                $retval += $stmt->rowCount();

                                $stmt = $this->_dbAdapter->prepare(
                                                'UPDATE '
                                                . $name
                                                . 'SET ' . $rightCol . ' = ' . $rightCol . '-' . $width . ' WHERE ' . $rightCol . '>' . $rgt);
                                $stmt->execute();
                                $retval += $stmt->rowCount();
                        }

                        return $retval;
                }
        }

        /**
         * Generates left and right column value, based on id of a
         * objective node.
         *
         * @param mixed Id of a objective node.
         * @param string Position in tree.
         * @return array
         */
        protected function _getLftRgt($objectiveNodeId, $position, $id = null) {
                $lftRgt = array();

                $name = $this->_quoteIdentifier($this->_name);
                $primary = $this->_quoteIdentifier($this->_primary);
                $left = $this->_quoteIdentifier($this->_left);
                $right = $this->_quoteIdentifier($this->_right);

                $lft = null;
                $rgt = null;

                if ($objectiveNodeId) {
                        $sql = "SELECT $left, $right FROM $name WHERE $primary = ?";
                        $stmt = $this->_dbAdapter->prepare($sql);
                        $stmt->bindParam(1, $objectiveNodeId, PDO::PARAM_INT);
                        $stmt->execute();
                        if ($stmt->rowCount() > 0) {
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $lft = (int) $result[$this->_left];
                                $rgt = (int) $result[$this->_right];
                        }
                }

                if ($lft !== null && $rgt !== null) { //Existing objective id?
                        $sql1 = '';
                        $sql2 = '';
                        switch ($position) {
                                case self::FIRST_CHILD :
                                        $sql1 = "UPDATE $name SET $right = $right + 2 WHERE $right > $lft";
                                        $sql2 = "UPDATE $name SET $left = $left + 2 WHERE $left > $lft";

                                        $lftRgt[$this->_left] = $lft + 1;
                                        $lftRgt[$this->_right] = $lft + 2;

                                        break;
                                case self::LAST_CHILD :
                                        $sql1 = "UPDATE $name SET $right = $right + 2 WHERE $right >= $rgt";
                                        $sql2 = "UPDATE $name SET $left = $left + 2 WHERE $left > $rgt";

                                        $lftRgt[$this->_left] = $rgt;
                                        $lftRgt[$this->_right] = $rgt + 1;

                                        break;
                                case self::NEXT_SIBLING :
                                        $sql1 = "UPDATE $name SET $right = $right + 2 WHERE $right > $rgt";
                                        $sql2 = "UPDATE $name SET $left = $left + 2 WHERE $left > $rgt";

                                        $lftRgt[$this->_left] = $rgt + 1;
                                        $lftRgt[$this->_right] = $rgt + 2;

                                        break;
                                case self::PREV_SIBLING :
                                        $sql1 = "UPDATE $name SET $right = $right + 2 WHERE $right > $lft";
                                        $sql2 = "UPDATE $name SET $left = $left + 2 WHERE $left >= $lft";

                                        $lftRgt[$this->_left] = $lft;
                                        $lftRgt[$this->_right] = $lft + 1;

                                        break;
                        }

                        $this->_dbAdapter->query($sql1);
                        $this->_dbAdapter->query($sql2);
                } else {
                        $sql = "SELECT MAX($right) AS \"max_rgt\" FROM $name";
                        if ($id !== null) {
                                $sql .= " WHERE $primary != ?";
                        }
                        $stmt = $this->_dbAdapter->prepare($sql);
                        if ($id !== null) {
                                $id = (int) $id;
                                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                        }

                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $lftRgt[$this->_left] = $result['max_rgt'] + 1;
                        } else {
                                //No data? First node...
                                $lftRgt[$this->_left] = 1;
                        }

                        $lftRgt[$this->_right] = $lftRgt[$this->_left] + 1;
                }

                return $lftRgt;
        }

        /**
         * Reduces lft and rgt values of some nodes, on which some 
         * node that is changing position in tree, or being deleted, 
         * has effect.
         *
         * @param int $id Id of a node.
         * @return void
         */
        protected function _reduceWidth($id) {
                $name = $this->_quoteIdentifier($this->_name);
                $primary = $this->_quoteIdentifier($this->_primary);
                $leftCol = $this->_quoteIdentifier($this->_left);
                $rightCol = $this->_quoteIdentifier($this->_right);

                $sql = "SELECT $leftCol, $rightCol, ($rightCol - $leftCol + 1) AS \"width\" FROM $name WHERE $primary = ?";
                $stmt = $this->_dbAdapter->prepare($sql);
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) { //Only if supplied node exists.
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);

                        $left = $result[$this->_left];
                        $right = $result[$this->_right];
                        $width = $result['width'];

                        if ((int) $width > 2) { //Some node that has childs.
                                //Updating children.
                                $sql = "UPDATE $name SET $rightCol = $rightCol - 1, $leftCol = $leftCol - 1 WHERE $leftCol BETWEEN $left AND $right";
                                $this->_dbAdapter->query($sql);
                        }

                        //Updating parent nodes and nodes on next levels.
                        $sql = "UPDATE $name SET $leftCol = $leftCol - 2 WHERE $leftCol > $left AND $rightCol > $right";
                        $this->_dbAdapter->query($sql);

                        $sql = "UPDATE $name SET $rightCol = $rightCol - 2 WHERE $rightCol > $right";
                        $this->_dbAdapter->query($sql);
                }
        }

        /**
         * Gets id of some node's current objective node.
         *
         * @param mixed Node id.
         * @param string Position in tree.
         * @return int|null
         */
        protected function _getCurrentObjectiveId($nodeId, $position) {
                $sql = '';

                $nodeId = $this->_dbAdapter->quote($nodeId, PDO::PARAM_INT);
                $name = $this->_quoteIdentifier($this->_name);
                $primary = $this->_quoteIdentifier($this->_primary);
                $leftCol = $this->_quoteIdentifier($this->_left);
                $rightCol = $this->_quoteIdentifier($this->_right);

                switch ($position) {
                        case self::FIRST_CHILD :
                                $sql = "SELECT node.$primary
                FROM $name node, (SELECT $leftCol, $rightCol FROM $name WHERE $primary = $nodeId) AS current
                WHERE current.$leftCol BETWEEN node.$leftCol+1 AND node.$rightCol AND current.$leftCol - node.$leftCol = 1
                ORDER BY node.$leftCol DESC";

                                break;
                        case self::LAST_CHILD :
                                $sql = "SELECT node.$primary
                FROM $name node, (SELECT $leftCol, $rightCol FROM $name WHERE $primary = $nodeId) AS current
                WHERE current.$leftCol BETWEEN node.$leftCol+1 AND node.$rightCol AND node.$rightCol - current.$rightCol = 1
                ORDER BY node.$leftCol DESC";

                                break;
                        case self::NEXT_SIBLING :
                                $sql = "SELECT node.$primary
                FROM $name node, (SELECT $leftCol FROM $name WHERE $primary = $nodeId) AS current
                WHERE current.$leftCol - node.$rightCol = 1";

                                break;
                        case self::PREV_SIBLING :
                                $sql = "SELECT node.$primary
                FROM $name node, (SELECT $rightCol FROM $name WHERE $primary = $nodeId) AS current
                WHERE node.$leftCol - current.$rightCol = 1";

                                break;
                }

                $stmt = $this->_dbAdapter->prepare($sql);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);

                        return (int) $result[$this->_primary];
                } else {
                        return null;
                }
        }

}