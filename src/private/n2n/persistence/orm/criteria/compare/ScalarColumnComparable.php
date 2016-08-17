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
namespace n2n\persistence\orm\criteria\compare;

use n2n\reflection\ArgUtils;
use n2n\persistence\meta\data\QueryItem;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\reflection\property\TypeConstraint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryPartGroup;

class ScalarColumnComparable extends ColumnComparableAdapter {
	private $queryState;
	
	public function __construct(QueryItem $comparableQueryItem, QueryState $queryState) {
		parent::__construct(CriteriaComparator::getOperators(false), 
				TypeConstraint::createSimple('scalar', true), $comparableQueryItem);
		
		$this->queryState = $queryState;
	}
	
	public function buildCounterpartQueryItemFromValue($operator, $value) {
		if ($operator != CriteriaComparator::OPERATOR_IN) {
			ArgUtils::valType($value, 'scalar', true);
			return new QueryPlaceMarker($this->queryState->registerPlaceholderValue($value));
		} 
		
		ArgUtils::valArray($value, 'scalar');
		
		$queryPartGroup = new QueryPartGroup();
		foreach ($value as $fieldValue) {
			$queryPartGroup->addQueryPart(
					new QueryPlaceMarker($this->queryState->registerPlaceholderValue($fieldValue)));
		}
		return $queryPartGroup;
	}
	
	public function buildCounterpartPlaceholder($operator, $value) {
		
	}
	
// 	public function parseComparableValue($operator, $value) {
// 		
		
// 		return $value;
// 	}
	
}
