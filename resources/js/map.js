import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

const customLocationIconUrl = '/images/map/Icono_Ubicacion.png';
const customIconOptions = {
    iconRetinaUrl: customLocationIconUrl,
    iconUrl: customLocationIconUrl,
    shadowUrl,
    iconSize: [36, 52],
    iconAnchor: [18, 52],
    popupAnchor: [0, -44],
    shadowSize: [41, 41],
    shadowAnchor: [13, 41],
};
const customMarkerIcon = L.icon(customIconOptions);

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions(customIconOptions);

const locale = document.documentElement.lang?.startsWith('en') ? 'en' : 'es';
const t = {
    layerHybrid: locale === 'en' ? 'Satellite (hybrid)' : 'Satélite (híbrido)',
    layerStreets: locale === 'en' ? 'Streets (OpenStreetMap)' : 'Calles (OpenStreetMap)',
    viewWeapon: locale === 'en' ? 'View weapon' : 'Ver arma',
    weaponCount: locale === 'en' ? 'Weapon count' : 'Cantidad de armas',
    serial: locale === 'en' ? 'Serial' : 'Serie',
    detail: locale === 'en' ? 'Detail' : 'Detalle',
    noResults: locale === 'en' ? 'No matches found.' : 'No se encontraron coincidencias.',
    writeSerial: locale === 'en' ? 'Type at least 2 characters.' : 'Escribe al menos 2 caracteres.',
    clearSearch: locale === 'en' ? 'Clear' : 'Limpiar',
};

const normalizeText = (value) => {
    if (!value) {
        return '';
    }
    return value
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
};

const initMap = () => {
    const mapElement = document.getElementById('weapons-map');
    const searchInput = document.getElementById('weapons-map-search');
    const searchResults = document.getElementById('weapons-map-search-results');
    const searchShell = document.getElementById('weapons-map-search-shell');
    if (!mapElement) {
        return;
    }

    const endpoint = mapElement.dataset.endpoint;
    const map = L.map(mapElement).setView([4.5709, -74.2973], 5);

    const esriAttribution =
        'Tiles &copy; <a href="https://www.esri.com/">Esri</a> — '
        + 'Earthstar Geographics, Maxar, OpenStreetMap & contributors';

    const osmStreets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    });

    const esriImagery = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 19, attribution: esriAttribution },
    );
    const esriTransport = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 19, attribution: '&copy; Esri', opacity: 0.9 },
    );
    const esriPlaces = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 19, attribution: '&copy; Esri' },
    );
    const hybridBase = L.layerGroup([esriImagery, esriTransport, esriPlaces]);

    const baseLayers = {
        [t.layerHybrid]: hybridBase,
        [t.layerStreets]: osmStreets,
    };
    hybridBase.addTo(map);
    L.control.layers(baseLayers, {}, { position: 'topright', collapsed: false }).addTo(map);

    let searchIndex = [];
    let searchDebounce = null;

    const hideSearchResults = () => {
        if (!searchResults) {
            return;
        }
        searchResults.classList.add('hidden');
        searchResults.innerHTML = '';
    };

    const zoomToItem = (item) => {
        if (!item) {
            return;
        }
        map.setView([item.lat, item.lng], 16, { animate: true });
        if (item.marker) {
            item.marker.openPopup();
        }
    };

    const renderSearchResults = (items) => {
        if (!searchResults) {
            return;
        }
        if (!items.length) {
            searchResults.innerHTML = `<div class="px-3 py-2 text-sm text-gray-500">${t.noResults}</div>`;
            searchResults.classList.remove('hidden');
            return;
        }

        const shown = items.slice(0, 8);
        const rows = shown
            .map(
                (item, index) => `
                    <button
                        type="button"
                        class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-blue-50"
                        data-weapon-result-index="${index}"
                    >
                        <span class="block font-medium text-gray-800">${item.serial || '-'}</span>
                        <span class="block text-xs text-gray-500">${item.client || '-'}</span>
                    </button>
                `
            )
            .join('');
        searchResults.innerHTML = rows;
        searchResults.classList.remove('hidden');

        Array.from(searchResults.querySelectorAll('[data-weapon-result-index]')).forEach((button) => {
            button.addEventListener('click', () => {
                const index = Number.parseInt(button.dataset.weaponResultIndex || '', 10);
                const item = shown[index];
                if (!item) {
                    return;
                }
                searchInput.value = item.serial || '';
                hideSearchResults();
                zoomToItem(item);
            });
        });
    };

    if (searchInput && searchResults) {
        searchInput.insertAdjacentHTML(
            'afterend',
            `<button id="weapons-map-search-clear" type="button" class="mt-2 text-xs text-blue-700 hover:text-blue-900">${t.clearSearch}</button>`
        );
        const clearButton = document.getElementById('weapons-map-search-clear');

        searchInput.addEventListener('input', (event) => {
            const query = normalizeText(event.target.value || '');
            if (searchDebounce) {
                clearTimeout(searchDebounce);
            }
            searchDebounce = setTimeout(() => {
                if (query.length < 2) {
                    searchResults.innerHTML = `<div class="px-3 py-2 text-sm text-gray-500">${t.writeSerial}</div>`;
                    searchResults.classList.remove('hidden');
                    return;
                }
                const filtered = searchIndex.filter((item) => {
                    const serialMatch = normalizeText(item.serial).includes(query);
                    const clientMatch = normalizeText(item.client).includes(query);
                    return serialMatch || clientMatch;
                });
                renderSearchResults(filtered);
            }, 180);
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            const firstButton = searchResults.querySelector('[data-weapon-result-index="0"]');
            if (!firstButton) {
                return;
            }
            event.preventDefault();
            firstButton.click();
        });

        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                hideSearchResults();
            });
        }

        document.addEventListener('click', (event) => {
            if (!searchShell || searchShell.contains(event.target)) {
                return;
            }
            hideSearchResults();
        });
    }

    if (!endpoint) {
        return;
    }

    fetch(endpoint)
        .then((response) => response.json())
        .then((items) => {
            if (!Array.isArray(items) || items.length === 0) {
                return;
            }

            const grouped = new Map();
            items.forEach((item) => {
                const key = `${item.lat},${item.lng}`;
                if (!grouped.has(key)) {
                    grouped.set(key, []);
                }
                grouped.get(key).push(item);
            });

            const clusterGroup = L.markerClusterGroup({
                iconCreateFunction: (cluster) => {
                    const count = cluster.getChildCount();
                    return L.divIcon({
                        html: `
                            <div style="position:relative;width:26px;height:41px;">
                                <img src="${customLocationIconUrl}" alt="" style="width:26px;height:41px;display:block;object-fit:contain;" />
                                <span style="position:absolute;right:-6px;top:-6px;background:#1f6fb2;color:#fff;font-size:11px;font-weight:700;line-height:1;padding:4px 6px;border-radius:999px;border:2px solid #fff;box-shadow:0 4px 10px rgba(15,23,42,.25);">${count}</span>
                            </div>
                        `,
                        className: 'sj-weapons-cluster-icon',
                        iconSize: [36, 52],
                        iconAnchor: [18, 52],
                        popupAnchor: [1, -44],
                    });
                },
            });

            const popupOptions = {
                className: 'sj-weapons-map-popup',
                maxWidth: 360,
                autoPan: true,
                autoPanPadding: L.point(40, 40),
                keepInView: false,
            };
            const bounds = [];

            grouped.forEach((groupItems) => {
                const { lat, lng } = groupItems[0];
                const clientName = groupItems[0].client ?? '-';
                const rows = groupItems
                    .map(
                        (item) => `
                    <tr>
                        <td class="pr-3 py-1">${item.serial_number ?? '-'}</td>
                        <td class="py-1 text-right">
                            <a href="${item.link}" target="_blank" rel="noopener noreferrer">${t.viewWeapon}</a>
                        </td>
                    </tr>
                `
                    )
                    .join('');
                const popup = `
                    <div class="text-sm sj-weapons-map-popup-inner">
                        <div class="font-semibold mb-1">${clientName}</div>
                        <div class="mb-2 text-xs text-gray-600">${t.weaponCount}: ${groupItems.length}</div>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th class="pr-3 pb-1">${t.serial}</th>
                                    <th class="pb-1 text-right">${t.detail}</th>
                                </tr>
                            </thead>
                        </table>
                        <div class="sj-weapons-map-popup__table-wrap">
                            <table class="w-full text-xs">
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                `;

                const marker = L.marker([lat, lng]);
                marker.setIcon(customMarkerIcon);
                marker.bindPopup(popup, popupOptions);
                clusterGroup.addLayer(marker);
                bounds.push([lat, lng]);

                groupItems.forEach((item) => {
                    searchIndex.push({
                        serial: item.serial_number ?? '',
                        client: item.client ?? '',
                        lat,
                        lng,
                        marker,
                    });
                });
            });

            map.addLayer(clusterGroup);

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30] });
            }
        })
        .catch(() => {});
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMap);
} else {
    initMap();
}
