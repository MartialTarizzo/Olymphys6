<?php


namespace App\Repository;


use App\Entity\Coefficients;
use App\Entity\Notes;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Notes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notes[]    findAll()
 * @method Notes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotesRepository extends EntityRepository
{

    public function get_rangs($jure_id, $coef)//sans l'écrit
    {
        $queryBuilder = $this->createQueryBuilder('n');  // e est un alias, un raccourci donné à l'entité du repository. 1ère lettre du nom de l'entité

        // On ajoute des critères de tri, etc.

        $queryBuilder
            ->where('n.jure=:jure_id')
            ->setParameter('jure_id', $jure_id);

        //->orderBy('n.exper*' . $coef->getExper() . ' + n.demarche*' . $coef->getDemarche() . ' + n.oral*' . $coef->getOral() . ' + n.origin*' . $coef->getOrigin() . ' + n.wgroupe*' . $coef->getWgroupe() . ' + n.ecrit*' . $coef->getEcrit() . ' + n.repquestions*' . $coef->getRepquestions(), 'DESC');

        // on récupère la query
        $query = $queryBuilder->getQuery();

        // getResult() exécute la requête et retourne un tableau contenant les résultats sous forme d'objets.
        // Utiliser getArrayResult en cas d'affichage simple : le résultat est sous forme de tableau : plus rapide que getResult()
        $results = $query->getResult();//toutes les notes évaluées par le juré
        $j = 0;
        $total = [];
        foreach ($results as $result) {
            $id = $result->getEquipe()->getId();
            $total[$id] = $result->getExper() * $coef->getExper() + $result->getDemarche() * $coef->getDemarche() + $result->getOral() * $coef->getOral() + $result->getOrigin() * $coef->getOrigin() + $result->getWgroupe() * $coef->getWgroupe() + $result->getRepquestions() * $coef->getRepquestions();//' + n.ecrit*' . $coef->getEcrit() . '
        }
        arsort($total);
        $i = 1;
        $rangs = [];
        foreach ($total as $key => $value) {
            foreach ($results as $result) {
                if ($result->getEquipe()->getId() == $key) {
                    $id = $result->getEquipe()->getId();
                    $rangs[$id] = $i;
                }
            }
            $i = $i + 1;

        }
        return $rangs;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function EquipeDejaNotee($jure_id, $equipe_id)//donne la note si l'équipe a déjà une note de ce juré
    {
        $queryBuilder = $this->createQueryBuilder('n');  // n est un alias, un raccourci donné à l'entité du repository. 1ère lettre du nom de l'entité

        // On ajoute des critères de tri, etc.
        $queryBuilder
            ->where('n.jure=:jure_id')
            ->setParameter('jure_id', $jure_id)
            ->andwhere('n.equipe=:equipe_id')
            ->setParameter('equipe_id', $equipe_id);

        // on récupère la query
        $query = $queryBuilder->getQuery();

        // getResult() exécute la requête et retourne un tableau contenant les résultats sous forme d'objets.
        // Utiliser getArrayResult en cas d'affichage simple : le résultat est sous forme de tableau : plus rapide que getResult()
        $results = $query->getOneOrNullResult();

        // on retourne ces résultats
        return $results;

    }

}


