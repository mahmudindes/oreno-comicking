<?php

namespace App\Repository;

use App\Entity\ComicTag;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicTag>
 */
class ComicTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicTag::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.comic', 'cc')->addSelect('cc')
            ->leftJoin('c.tag', 'ct')->addSelect('ct')
            ->leftJoin('ct.type', 'ctt')->addSelect('ctt');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'tagTypeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('ctt.code = :tagTypeCode');
                        $query->setParameter('tagTypeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ctt.code IN (:tagTypeCodes)');
                    $query->setParameter('tagTypeCodes', $val);
                    break;
                case 'tagCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('ct.code = :tagCode');
                        $query->setParameter('tagCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ct.code IN (:tagCodes)');
                    $query->setParameter('tagCodes', $val);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 4) break;

                switch ($val->name) {
                    case 'comicCode':
                        $val->name = 'cc.code';
                        break;
                    case 'tagTypeCode':
                        $val->name = 'ctt.code';
                        break;
                    case 'tagCode':
                        $val->name = 'ct.code';
                        break;
                    case 'createdAt':
                        $val->name = 'c.' . $val->name;
                        break;
                    default:
                        continue 2;
                }

                switch (\strtolower($val->order ?? '')) {
                    case 'a':
                    case 'asc':
                    case 'ascending':
                        $val->order = 'ASC';
                        break;
                    case 'd':
                    case 'desc':
                    case 'descending':
                        $val->order = 'DESC';
                        break;
                    default:
                        $val->order = null;
                }

                switch (\strtolower($val->nulls ?? '')) {
                    case 'f':
                    case 'first':
                        $val->nulls = 'DESC';
                        break;
                    case 'l':
                    case 'last':
                        $val->nulls = 'ASC';
                        break;
                    default:
                        $val->nulls = null;
                }

                if ($val->nulls) {
                    $vname = \str_replace('.', '', $val->name . $val);
                    $vselc = '(CASE WHEN ' . $val->name . ' IS NULL THEN 1 ELSE 0 END) AS HIDDEN ' . $vname;

                    $query->addSelect($vselc);
                    $query->addOrderBy($vname, $val->nulls);
                }

                $query->addOrderBy($val->name, $val->order);
            }
        } else {
            $query->orderBy('ctt.code');
            $query->orderBy('ct.code');
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->setCacheable(true)->getQuery()->getResult();
    }

    public function countCustom(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('c')
            ->select('count(c.id)');

        $q01 = false;
        $q01Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.comic', 'cc');
            $c = true;
        };
        $q02 = false;
        $q02Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.tag', 'ct');
            $c = true;
        };
        $q03 = false;
        $q03Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('ct.type', 'ctt');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'tagTypeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q03Func($q03, $query);

                    if ($c == 1) {
                        $query->andWhere('ctt.code = :tagTypeCode');
                        $query->setParameter('tagTypeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ctt.code IN (:tagTypeCodes)');
                    $query->setParameter('tagTypeCodes', $val);
                    break;
                case 'tagCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q02Func($q02, $query);

                    if ($c == 1) {
                        $query->andWhere('ct.code = :tagCode');
                        $query->setParameter('tagCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ct.code IN (:tagCodes)');
                    $query->setParameter('tagCodes', $val);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
