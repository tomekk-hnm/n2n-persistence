<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\persistence\orm\property\impl\relation\util;

use n2n\persistence\orm\store\action\ActionAdapter;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\Pdo;

class JoinColumnResetAction extends ActionAdapter {
	private $pdo;
	private $tableName;
	private $joinColumnName;
	
	private $joinIdRaw;
	
	public function __construct(Pdo $pdo, $tableName, $idColumnName, $joinColumnName) {
		$this->pdo = $pdo;
		$this->tableName = $tableName;
		$this->joinColumnName = $joinColumnName;
	}
	
	public function setJoinIdRaw($joinIdRaw) {
		$this->joinIdRaw = $joinIdRaw;
	}
	
	protected function exec() {
		$metaData = $this->pdo->getMetaData();
	
		$updateBuilder = $metaData->createUpdateStatementBuilder();
		$updateBuilder->setTable($this->tableName);
		$updateBuilder->addColumn(new QueryColumn($this->joinColumnName), new QueryPlaceMarker());
		$updateBuilder->getWhereComparator()->match(new QueryColumn($this->joinColumnName),
				QueryComparator::OPERATOR_EQUAL, new QueryPlaceMarker());
	
		$updateStmt = $this->pdo->prepare($updateBuilder->toSqlString());
		$updateStmt->execute(array(null, $this->joinIdRaw));
	}
}
