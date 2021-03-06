<?php
/**
 * Copyright © OpenGento, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Opengento\CountryStoreRedirect\Test\Unit\Model\Resolver;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\ObjectManagerInterface;
use Opengento\CountryStore\Api\CountryRepositoryInterface;
use Opengento\CountryStore\Api\CountryResolverInterface;
use Opengento\CountryStore\Api\Data\CountryInterface;
use Opengento\CountryStore\Model\CountryResolver;
use Opengento\CountryStore\Model\Resolver\ResolverFactory;
use Opengento\CountryStoreRedirect\Model\Resolver\CloudFare;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Opengento\CountryStoreRedirect\Model\Resolver\CloudFare
 */
class CloudFareTest extends TestCase
{
    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|CountryResolverInterface
     */
    private $resolver;

    /**
     * @var MockObject|CountryRepositoryInterface
     */
    private $countryRepository;

    private CloudFare $cloudFareResolver;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->resolver = $this->getMockForAbstractClass(CountryResolverInterface::class);
        $objectFactory = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $objectFactory->method('get')->willReturn($this->resolver);
        $this->countryRepository = $this->getMockForAbstractClass(CountryRepositoryInterface::class);
        $this->countryRepository->method('get')->willReturnMap([
            ['US', $this->createCountryMock('US')],
            ['FR', $this->createCountryMock('FR')]
        ]);

        $this->cloudFareResolver = new CloudFare(
            $this->request,
            new ResolverFactory(
                $objectFactory,
                [CountryResolver::DEFAULT_COUNTRY_RESOLVER_CODE => 'Vendor\Module\Model\Resolver']
            ),
            $this->countryRepository
        );
    }

    /**
     * @dataProvider countryData
     */
    public function testGetCountryCode(string $countryCode, string $defaultCountryCode): void
    {
        $this->request->expects($this->exactly(2))
            ->method('getServerValue')
            ->with(CloudFare::CF_HTTP_HEADER_IPCOUNTRY)
            ->willReturn($countryCode, null);
        $this->resolver->expects($this->once())
            ->method('getCountry')
            ->willReturn($this->createCountryMock($defaultCountryCode));

        $this->assertSame($countryCode, $this->cloudFareResolver->getCountry()->getCode());
        $this->assertSame($defaultCountryCode, $this->cloudFareResolver->getCountry()->getCode());
    }

    public function countryData(): array
    {
        return [
            ['US', 'US'],
            ['US', 'CA'],
        ];
    }

    private function createCountryMock(string $countryCode): MockObject
    {
        $countryMock = $this->getMockForAbstractClass(CountryInterface::class);
        $countryMock->method('getCode')->willReturn($countryCode);

        return $countryMock;
    }
}
