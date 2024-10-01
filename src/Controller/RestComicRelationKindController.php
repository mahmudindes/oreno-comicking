<?php

namespace App\Controller;

use App\Entity\ComicRelationKind;
use App\Model\OrderByDto;
use App\Repository\ComicRelationKindRepository;
use App\Util\UrlQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute as Routing;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/comic-relation-types',
    name: 'rest_comicrelationtype_'
)]
class RestComicRelationKindController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ComicRelationKindRepository $comicRelationKindRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('code', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->comicRelationKindRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->comicRelationKindRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comicRelationType']]);

        $response->setEtag(\crc32($response->getContent()));
        foreach ($result as $v) {
            $aLastModified = $response->getLastModified();
            $bLastModified = $v->getUpdatedAt() ?? $v->getCreatedAt();
            if (!$aLastModified || $aLastModified < $bLastModified) {
                $response->setLastModified($bLastModified);
            }
        }
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('', name: 'post', methods: [Request::METHOD_POST])]
    public function post(
        Request $request
    ): Response {
        $result = new ComicRelationKind();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['code'])) $result->setCode($content['code']);
                if (isset($content['name'])) $result->setName($content['name']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comicrelationtype_get', ['code' => $result->getCode()]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['comicRelationType']]);
    }

    #[Routing\Route('/{code}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $code
    ): Response {
        $result = $this->comicRelationKindRepository->findOneBy(['code' => $code]);
        if (!$result) throw new NotFoundHttpException('Comic Relation Type not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['comicRelationType']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{code}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $code
    ): Response {
        $result = $this->comicRelationKindRepository->findOneBy(['code' => $code]);
        if (!$result) throw new NotFoundHttpException('Comic Relation Type not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['code'])) $result->setCode($content['code']);
                if (isset($content['name'])) $result->setName($content['name']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_comicrelationtype_get', ['code' => $result->getCode()]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['comicRelationType']]);
    }

    #[Routing\Route('/{code}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $code
    ): Response {
        $result = $this->comicRelationKindRepository->findOneBy(['code' => $code]);
        if (!$result) throw new NotFoundHttpException('Comic Relation Type not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
