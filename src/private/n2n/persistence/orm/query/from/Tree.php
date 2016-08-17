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
namespace n2n\persistence\orm\query\from;

use n2n\reflection\ArgUtils;
use n2n\persistence\meta\data\JoinType;
use n2n\persistence\orm\OrmException;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\query\QueryPointResolver;
use n2n\persistence\orm\query\QueryConflictException;
use n2n\persistence\meta\data\SelectStatementBuilder;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\criteria\Criteria;
use n2n\persistence\orm\query\QueryModel;
use n2n\reflection\ReflectionUtils;

class Tree implements QueryPointResolver {
	private $queryState;
	private $treePoints = array();
	private $namedTreePoints = array();
	
// 	private $inheritedNamedTreePoints = array();
	private $inheritedQueryPointResolver = null;

	public function __construct(QueryState $queryState) {
		$this->queryState = $queryState;
	}

	private function validateAlias($alias) {
		if (!is_scalar($alias) || !mb_strlen($alias)) {
			throw new \InvalidArgumentException('Invalid criteria alias type: ' . ReflectionUtils::getTypeInfo($alias));
		}

		if (isset($this->namedTreePoint[$alias])) {
			throw new QueryConflictException('Criteria alias is ambiguous: ' . $alias);
		}
	}
	
	public function setInheritedQueryPointResolver(Tree $inheritedQueryPointResolver = null) {
		$this->inheritedQueryPointResolver = $inheritedQueryPointResolver;
	}
	
	public function getInheritedQueryPointResolver() {
		return $this->inheritedQueryPointResolver;
	}
	
// 	public function setInheritedNamedTreePoints(array $inheritedNamedTreePoints) {
// 		$this->inheritedNamedTreePoints = $inheritedNamedTreePoints;
// 	}
	
// 	public function getInheritedNamedTreePoints() {
// 		return $this->inheritedNamedTreePoints;
// 	}


	public function containsTreePointAlias($alias) {
		return isset($this->namedTreePoints[$alias]);
	}
	
	public function getTreePointByAlias($alias) {
		if (isset($this->namedTreePoints[$alias])) {
			return $this->namedTreePoints[$alias];
		}
		
		throw new QueryConflictException('Unknown alias: ' . $alias);
	}
	
	public function putNamedTreePoint($alias, TreePoint $treePoint) {
		$this->validateAlias($alias);
		$this->namedTreePoints[$alias] = $treePoint;
		$this->treePoints[] = $treePoint;
	}
	
	public function createBaseTreePoint(EntityModel $entityModel, $alias) {
		$this->validateAlias($alias);
		
		return $this->treePoints[] = $this->namedTreePoints[$alias] = new BaseEntityTreePoint(
				$this->queryState, $entityModel->createTreePointMeta($this->queryState));
	}	
	
	public function createJoinedEntityTreePoint($joinType, EntityModel $entityModel, $alias) {
		ArgUtils::valEnum($joinType, JoinType::getValues());
		$this->validateAlias($alias);
		
		$treePoint = new JoinedEntityTreePoint($this->queryState, 
				$entityModel->createTreePointMeta($this->queryState));
		$treePoint->setJoinType($joinType);
		return $this->treePoints[] = $this->namedTreePoints[$alias] = $treePoint;
	}
	
	public function createPropertyJoinedTreePoint($joinType, TreePath $treePath, $alias) {
		ArgUtils::valEnum($joinType, JoinType::getValues());
		$this->validateAlias($alias);
		
		$treePoint = $this->getTreePointByAlias($treePath->next());
		
		while ($treePath->hasNext()) {
			$this->treePoints[] = $treePoint = $treePoint->createPropertyJoinTreePoint(
					$treePath->next(), $joinType);
		}
		
		return $this->namedTreePoints[$alias] = $treePoint;
	}
	
	/**
	 * @param QueryModel $subQueryModel
	 * @param unknown $alias
	 * @return \n2n\persistence\orm\query\from\BaseSubCriteriaTreePoint
	 */
	public function createBaseCriteriaTreePoint(QueryModel $subQueryModel, $alias) {
		return $this->treePoints[] = $this->namedTreePoints[$alias] = new BaseSubCriteriaTreePoint(
				$subQueryModel, $this->queryState);
	}
	
	/**
	 * @param unknown $joinType
	 * @param QueryModel $subQueryModel
	 * @param unknown $alias
	 * @return \n2n\persistence\orm\query\from\JoinedSubCriteriaTreePoint
	 */
	public function createJoinedCriteriaTreePoint($joinType, QueryModel $subQueryModel, $alias) {
		$treePoint = new JoinedSubCriteriaTreePoint($subQueryModel, $this->queryState);
		$treePoint->setJoinType($joinType);
		
		return $this->treePoints[] = $this->namedTreePoints[$alias] = $treePoint;
	}
	
	public function apply(SelectStatementBuilder $selectBuilder) {
		foreach ($this->treePoints as $treePoint) {
			$treePoint->apply($selectBuilder);
		}
	}
// 	/**
// 	 * @param TreePath $treePath
// 	 * @return \n2n\persistence\orm\criteria\compare\ComparisonStrategy
// 	 * @throws QueryConflictException
// 	 */
// 	public function requestComparisonStrategy(TreePath $treePath) {
// 		try {
// 			$treePoint = $this->getTreePointByAlias($treePath->next());
// 			if ($treePath->hasNext()) {
// 				return $treePoint->requestPropertyComparisonStrategy($treePath);
// 			}
// 			return $treePoint->requestComparisonStrategy();
// 		} catch (OrmException $e) {
// 			throw new QueryConflictException('Could not request comparison strategy for comparison: '
// 					. TreePath::prettyPropertyStr($treePath->getAll()), 0, $e);
// 		}
// 	}
	
// 	public function createSelection(TreePath $treePath) {
// 		try {
// 			$treePoint = $this->getTreePointByAlias($treePath->next());
// 			if ($treePath->hasNext()) {
// 				return $treePoint->requestPropertySelection($treePath);
// 			}
// 			return $this->requestSelection();
// 		} catch (OrmException $e) {
// 			throw new QueryConflictException('Criteria property not selectable: '
// 					. TreePath::prettyPropertyStr($treePath->getDones()), 0, $e);
// 		}	
// 	}

// 	public function createRepresentableQueryItem(TreePath $treePath) {
// 		try {
// 			$treePoint = $this->getTreePointByAlias($treePath->next());
// 			if ($treePath->hasNext()) {
// 				return $treePoint->createPropertyRepresentableQueryItem($treePath);
// 			}
// 			return $treePath->createRepresentableQueryItem();
// 		} catch (OrmException $e) {
// 			throw new QueryConflictException('Criteria property not selectable: '
// 					. TreePath::prettyPropertyStr($treePath->getDones()), 0, $e);
// 		}
// 	}
	
	public function requestPropertyComparisonStrategy(TreePath $treePath) {
		if (!$this->containsTreePointAlias($treePath->getNext()) && $this->inheritedQueryPointResolver !== null) {
			return $this->inheritedQueryPointResolver->requestPropertyComparisonStrategy($treePath);
		}
		
		try {
			$treePoint = $this->getTreePointByAlias($treePath->next());
			if ($treePath->hasNext()) {
				return $treePoint->requestPropertyComparisonStrategy($treePath);
			}
			return $treePoint->requestComparisonStrategy();
		} catch (OrmException $e) {
			throw new QueryConflictException('Criteria property not comparable: '
					. TreePath::prettyPropertyStr($treePath->getDones()), 0, $e);
		}
	}
	
	public function requestPropertySelection(TreePath $treePath) {
		if (!$this->containsTreePointAlias($treePath->getNext()) && $this->inheritedQueryPointResolver !== null) {
			return $this->inheritedQueryPointResolver->requestPropertySelection($treePath);
		}
		
		try {
			$treePoint = $this->getTreePointByAlias($treePath->next());
			if ($treePath->hasNext()) {
				return $treePoint->requestPropertySelection($treePath);
			}
			return $treePoint->requestSelection();
		} catch (OrmException $e) {
			throw new QueryConflictException('Criteria property not selectable: '
					. TreePath::prettyPropertyStr($treePath->getDones()), 0, $e);
		}
	}
	
	public function requestPropertyRepresentableQueryItem(TreePath $treePath) {
		if (!$this->containsTreePointAlias($treePath->getNext()) && $this->inheritedQueryPointResolver !== null) {
			return $this->inheritedQueryPointResolver->requestPropertyRepresentableQueryItem($treePath);
		}
		
		try {
			$treePoint = $this->getTreePointByAlias($treePath->next());
			if ($treePath->hasNext()) {
				return $treePoint->requestPropertyRepresentableQueryItem($treePath);
			}
			return $treePoint->requestRepresentableQueryItem();
		} catch (OrmException $e) {
			throw new QueryConflictException('Criteria property not selectable: '
					. TreePath::prettyPropertyStr($treePath->getDones()), 0, $e);
		}
	}
}

// class Tree {
// 	private $entityModelManager;
// 	private $queryState;
	
// 	private $treePoints = array();

// 	public function __construct(QueryState $queryState) {
// 		$this->entityModelManager = EntityModelManager::getInstance();
// 		$this->queryState = $queryState;
// 	}

// 	public function addEntityToFromClause(\ReflectionClass $entityClass, $alias) {
// 		$this->addTreePointMetaToFromClause($this->entityModelManager->getEntityModelByClass($entityClass)
// 				->createTreePointMeta($this->queryState), $alias);
// 	}
	
// 	public function addTreePointMetaToFromClause(TreePointMeta $queryPoint, $alias) {
// 		$this->ensureAliasIsAvailable($alias);
		
// 		$this->treePoints[$alias] = new BaseTreePoint($queryPoint);
// 	}
	
// 	public function joinEntity(\ReflectionClass $entityClass, $alias, $joinType) {
// 		$this->ensureAliasIsAvailable($alias);

// 		$targetEntityModel = $this->entityModelManager->getEntityModelByClass($entityClass);
// 		$newTreePoint = new EntityJoinedTreePoint($targetEntityModel->createTreePointMeta($this->queryState), $joinType);

// 		$this->treePoints[$alias] = $newTreePoint;
		
// 		return $newTreePoint->getOnQueryComparator();
// 	}
	
	
// 	public function getTreePointMetaByAlias($alias) {
// 		if (isset($this->treePoints[$alias])) {
// 			return $this->treePoints[$alias]->getMeta();
// 		}	
// 		return null;
// 	}

// 	public function joinProperty(CriteriaProperty $criteriaProperty, $alias, $joinType) {
// 		$this->ensureAliasIsAvailable($alias);

// 		$nameParts = $criteriaProperty->getNameParts();
// 		$namePart = array_shift($nameParts);

// 		$this->treePoints[$alias] = $this->createTreePoint($this->getTreePointByAlias($namePart), 
// 				$nameParts, array($namePart), $joinType);
// 	}

// 	private function createTreePoint(TreePoint $parentTreePoint, array $nameParts, array $parentNameParts, $joinType) {
// 		if (!sizeof($nameParts)) return $parentTreePoint;

// 		$parentTreePointMeta = $parentTreePoint->getMeta();
// 		$namePart = array_shift($nameParts);
// 		$parentNameParts[] = $namePart;
// 		$criteriaProperty = new CriteriaProperty($parentNameParts);

// 		$entityProperty = QueryState::extractEntityProperty($parentTreePointMeta->getEntityModel(), 
// 				$namePart, $criteriaProperty);
// 		QueryState::ensureJoinable($entityProperty, $criteriaProperty);
			
// 		$targetEntityModel = $this->entityModelManager->getEntityModelByClass($entityProperty->getTargetEntityClass());
		
// 		$newTreePoint = new PropertyJoinedTreePoint($this->queryState, $parentTreePointMeta, $entityProperty, 
// 				$targetEntityModel->createTreePointMeta($this->queryState), $joinType);
// 		$parentTreePoint->addCustomTreePoint($newTreePoint);

// 		return $this->createTreePoint($newTreePoint, $nameParts, $parentNameParts, $joinType);
// 	}

// 	public function requestTreePointMeta(CriteriaProperty $criteriaProperty, $innerJoinRequired) {
// 		$nameParts = $criteriaProperty->getNameParts();
// 		$namePart = array_shift($nameParts);

// 		return $this->requestTreePoint($this->getTreePointByAlias($namePart), $nameParts, array($namePart), $innerJoinRequired)
// 				->getMeta();
// 	}

// 	private function requestTreePoint(TreePoint $parentTreePoint, array $nameParts, array $parentNameParts, $innerJoinRequired) {
// 		if (!sizeof($nameParts)) return $parentTreePoint;

// 		$namePart = array_shift($nameParts);
// 		$parentNameParts[] = $namePart;
// 		$requestedTreePoint = $parentTreePoint->getRequestedTreePoint($namePart);
// 		if (isset($requestedTreePoint)) {
// 			if ($innerJoinRequired && $requestedTreePoint->getJoinType() != JoinType::INNER) {
// 				$requestedTreePoint->setJoinType(JoinType::INNER);
// 			}
// 		} else {
// 			$criteriaProperty = new CriteriaProperty($parentNameParts);
// 			$parentTreePointMeta = $parentTreePoint->getMeta();
// 			$entityProperty = QueryState::extractEntityProperty($parentTreePointMeta->getEntityModel(),
// 					$namePart, $criteriaProperty);
// 			QueryState::ensureJoinable($entityProperty, $criteriaProperty);
				
// 			$targetEntityModel = $this->entityModelManager->getEntityModelByClass($entityProperty->getTargetEntityClass());
// 			$requestedTreePoint = new PropertyJoinedTreePoint($this->queryState, $parentTreePointMeta, $entityProperty, $targetEntityModel->createTreePointMeta($this->queryState), 
// 					($innerJoinRequired ? JoinType::INNER : JoinType::LEFT));
// 			$parentTreePoint->setRequestedTreePoint($namePart, $requestedTreePoint);
// 		}

// 		return $this->requestTreePoint($requestedTreePoint, $nameParts, $parentNameParts, $innerJoinRequired);
// 	}
	
// 	public function apply(SelectStatementBuilder $selectBuilder) {
// 		foreach ($this->treePoints as $treePoint) {
// 			if ($treePoint instanceof BaseTreePoint) {
// 				$treePoint->apply($selectBuilder);
// 			}
// 		}
// 	}
// }
