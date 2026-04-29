import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';
import municipios from '../data/colombia_municipios.json';

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
    select: locale === 'en' ? 'Select' : 'Seleccione',
    searching: locale === 'en' ? 'Searching...' : 'Buscando...',
    searchByName: locale === 'en' ? 'Search by city, municipality or department' : 'Buscar por ciudad, municipio o departamento',
    noResults: locale === 'en' ? 'No matches found.' : 'No se encontraron resultados.',
    incompleteAddress: locale === 'en'
        ? 'Address or municipality could not be completed. Adjust it manually if needed.'
        : 'No se pudo completar la dirección o el municipio. Ajusta manualmente si es necesario.',
    geocodeFailed: locale === 'en'
        ? 'Location could not be obtained. Try again or complete the data manually.'
        : 'No se pudo obtener la ubicación. Intenta nuevamente o completa los datos manualmente.',
    manualAddressInvalid: locale === 'en'
        ? 'Address not recognized. You can save it as is or choose the location on the map.'
        : 'Dirección no reconocida. Puedes guardarla así o seleccionar la ubicación en el mapa.',
    layerHybrid: locale === 'en' ? 'Satellite (hybrid)' : 'Satélite (híbrido)',
    layerStreets: locale === 'en' ? 'Streets (OpenStreetMap)' : 'Calles (OpenStreetMap)',
};

const buildOption = (value, label) => {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = label;
    return option;
};

const municipalityAliases = {
    CALI: 'SANTIAGO DE CALI',
};

const getMunicipalityLabel = (municipality) => {
    if (municipality === 'SANTIAGO DE CALI') {
        return 'CALI';
    }

    return municipality;
};

const populateDepartments = (select, selected) => {
    const departments = Object.keys(municipios).sort();
    departments.forEach((department) => {
        select.appendChild(buildOption(department, department));
    });
    if (selected) {
        select.value = selected;
    }
};

const populateMunicipalities = (select, department, selected) => {
    select.innerHTML = '';
    select.appendChild(buildOption('', t.select));
    if (!department || !municipios[department]) {
        return;
    }
    municipios[department].forEach((municipality) => {
        select.appendChild(buildOption(municipality, getMunicipalityLabel(municipality)));
    });
    if (selected) {
        if (!selectByNormalizedMatch(select, selected)) {
            select.value = selected;
        }
    }
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

const selectByNormalizedMatch = (select, value) => {
    if (!select || !value) {
        return false;
    }
    const target = normalizeText(value);
    if (!target) {
        return false;
    }
    const options = Array.from(select.options);
    const targetAlias = municipalityAliases[target.toUpperCase()] || null;
    const exactMatch = options.find((option) => {
        const optionValue = normalizeText(option.value);
        const optionLabel = normalizeText(option.textContent);

        return optionValue === target
            || optionLabel === target
            || (targetAlias && option.value === targetAlias);
    });
    if (exactMatch) {
        select.value = exactMatch.value;
        return true;
    }
    const simplified = target
        .replace(/\bmunicipio\b/g, '')
        .replace(/\bciudad\b/g, '')
        .replace(/\bcity\b/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    if (simplified) {
        const partialMatch = options.find((option) => normalizeText(option.value) === simplified);
        if (partialMatch) {
            select.value = partialMatch.value;
            return true;
        }
    }
    const containsMatch = options.find((option) => {
        const candidate = normalizeText(option.value);
        return candidate && (target.includes(candidate) || candidate.includes(target));
    });
    if (containsMatch) {
        select.value = containsMatch.value;
        return true;
    }
    return false;
};

const normalizeDepartmentName = (value) => {
    const base = normalizeText(value)
        .replace(/\bdepartamento\b/g, '')
        .replace(/\bdistrito\b/g, '')
        .replace(/\bcapital\b/g, '')
        .replace(/\bd\.?\s*c\.?\b/g, '')
        .replace(/\bde\b/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    if (!base) {
        return '';
    }
    if (base.includes('bogota')) {
        return 'BOGOTA D.C.';
    }
    if (base === 'cali' || base.endsWith(' cali') || base.includes('santiago de cali')) {
        return 'SANTIAGO DE CALI';
    }
    return base.toUpperCase();
};

const normalizeMunicipalityName = (value) => {
    const base = normalizeText(value)
        .replace(/\bmunicipio\b/g, '')
        .replace(/\bdistrito\b/g, '')
        .replace(/\bd\.?\s*c\.?\b/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    if (!base) {
        return '';
    }
    if (base.includes('bogota')) {
        return 'BOGOTA D.C.';
    }
    return base.toUpperCase();
};

const initLocationSelects = () => {
    const departmentSelects = document.querySelectorAll('[data-department-select]');
    if (!departmentSelects.length) {
        return;
    }

    departmentSelects.forEach((departmentSelect) => {
        const form = departmentSelect.closest('[data-location-form]');
        if (!form) {
            return;
        }
        const municipalitySelect = form.querySelector('[data-municipality-select]');
        if (!municipalitySelect) {
            return;
        }

        const currentDepartment = departmentSelect.dataset.current || '';
        const currentMunicipality = municipalitySelect.dataset.current || '';

        departmentSelect.innerHTML = '';
        departmentSelect.appendChild(buildOption('', t.select));
        populateDepartments(departmentSelect, currentDepartment);
        populateMunicipalities(municipalitySelect, currentDepartment, currentMunicipality);

        departmentSelect.addEventListener('change', (event) => {
            populateMunicipalities(municipalitySelect, event.target.value, '');
        });
    });
};

const initMapPicker = () => {
    const triggers = document.querySelectorAll('[data-map-trigger]');
    if (!triggers.length) {
        return;
    }

    const modal = document.getElementById('location-map-modal');
    const closeButtons = modal ? modal.querySelectorAll('[data-map-close]') : [];
    const mapElement = modal ? modal.querySelector('#location-map') : null;
    const acceptButton = modal ? modal.querySelector('[data-map-accept]') : null;
    const errorMessage = modal ? modal.querySelector('[data-map-error]') : null;
    if (!modal || !mapElement || !mapElement.parentNode) {
        return;
    }

    const searchWrapper = document.createElement('div');
    searchWrapper.className = 'mt-2';
    searchWrapper.innerHTML = `
        <label for="location-map-search" class="mb-1 block text-sm font-medium text-gray-700">${t.searchByName}</label>
        <div class="relative">
            <input
                id="location-map-search"
                type="text"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="${t.searchByName}"
                autocomplete="off"
                data-map-search-input
            />
            <div class="absolute left-0 right-0 top-full z-20 mt-1 max-h-60 overflow-auto rounded-md border border-gray-200 bg-white shadow-lg hidden" data-map-search-results></div>
        </div>
    `;
    mapElement.parentNode.insertBefore(searchWrapper, mapElement);

    const searchInput = searchWrapper.querySelector('[data-map-search-input]');
    const searchResults = searchWrapper.querySelector('[data-map-search-results]');

    let mapInstance = null;
    let marker = null;
    let selectedLatLng = null;
    let activeForm = null;
    let searchDebounce = null;
    let searchAbortController = null;
    const geocodeDebounces = new WeakMap();
    const geocodeAbortControllers = new WeakMap();

    const resolveInputs = (form = activeForm) => {
        if (!form) {
            return {};
        }
        return {
            latInput: form.querySelector('[data-latitude-input]'),
            lngInput: form.querySelector('[data-longitude-input]'),
            addressInput: form.querySelector('[data-address-input]'),
            neighborhoodInput: form.querySelector('[data-neighborhood-input]'),
            departmentSelect: form.querySelector('[data-department-select]'),
            municipalitySelect: form.querySelector('[data-municipality-select]'),
            coordsSourceInput: form.querySelector('[data-coords-source]'),
            notice: form.querySelector('[data-geocode-notice]'),
        };
    };

    const setNotice = (form, message = '') => {
        const { notice } = resolveInputs(form);
        if (!notice) {
            return;
        }
        if (!message) {
            notice.textContent = '';
            notice.classList.add('hidden');
            return;
        }
        notice.textContent = message;
        notice.classList.remove('hidden');
    };

    const setCoordinates = (form, lat, lng, source = 'geocode') => {
        const { latInput, lngInput, coordsSourceInput } = resolveInputs(form);
        if (latInput) {
            latInput.value = Number(lat).toFixed(6);
        }
        if (lngInput) {
            lngInput.value = Number(lng).toFixed(6);
        }
        if (coordsSourceInput) {
            coordsSourceInput.value = source;
        }
    };

    const clearCoordinates = (form, source = 'geocode') => {
        const { latInput, lngInput, coordsSourceInput } = resolveInputs(form);
        if (latInput) {
            latInput.value = '';
        }
        if (lngInput) {
            lngInput.value = '';
        }
        if (coordsSourceInput) {
            coordsSourceInput.value = source;
        }
    };

    const collectLocationData = (form) => {
        const { addressInput, neighborhoodInput, municipalitySelect, departmentSelect } = resolveInputs(form);

        return {
            address: addressInput?.value?.trim() || '',
            neighborhood: neighborhoodInput?.value?.trim() || '',
            city: municipalitySelect?.value?.trim() || '',
            department: departmentSelect?.value?.trim() || '',
        };
    };

    const hasEnoughDataToGeocode = (data) => data.address.length >= 5 && data.city !== '' && data.department !== '';

    const geocodeManualLocation = async (form) => {
        const data = collectLocationData(form);

        if (!hasEnoughDataToGeocode(data)) {
            setNotice(form, '');
            return;
        }

        const previousAbort = geocodeAbortControllers.get(form);
        if (previousAbort) {
            previousAbort.abort();
        }

        const controller = new AbortController();
        geocodeAbortControllers.set(form, controller);

        const url = new URL('/geocode/search', window.location.origin);
        url.searchParams.set('address', data.address);
        url.searchParams.set('city', data.city);
        url.searchParams.set('department', data.department);
        if (data.neighborhood) {
            url.searchParams.set('neighborhood', data.neighborhood);
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                clearCoordinates(form);
                setNotice(form, t.manualAddressInvalid);
                return;
            }

            const result = await response.json();
            if (typeof result?.lat !== 'number' || typeof result?.lng !== 'number') {
                clearCoordinates(form);
                setNotice(form, t.manualAddressInvalid);
                return;
            }

            setCoordinates(form, result.lat, result.lng, 'geocode');
            setNotice(form, '');
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            clearCoordinates(form);
            setNotice(form, t.manualAddressInvalid);
        }
    };

    const scheduleManualGeocode = (form) => {
        const previousTimer = geocodeDebounces.get(form);
        if (previousTimer) {
            clearTimeout(previousTimer);
        }

        const timer = setTimeout(() => {
            geocodeManualLocation(form);
        }, 650);

        geocodeDebounces.set(form, timer);
    };

    const hideSearchResults = () => {
        searchResults.classList.add('hidden');
        searchResults.innerHTML = '';
    };

    const setSelectedLocation = (lat, lng, zoom = 14) => {
        if (!mapInstance) {
            return;
        }
        if (marker) {
            marker.setLatLng([lat, lng]);
            marker.setIcon(customMarkerIcon);
        } else {
            marker = L.marker([lat, lng], { icon: customMarkerIcon }).addTo(mapInstance);
        }
        selectedLatLng = { lat, lng };
        mapInstance.setView([lat, lng], zoom);

        setCoordinates(activeForm, lat, lng, 'map');
        setNotice(activeForm, '');
        if (acceptButton) {
            acceptButton.disabled = false;
        }
    };

    const guessZoomLevel = (item) => {
        const type = normalizeText(item.type || item.addresstype || '');
        if (type.includes('state') || type.includes('region') || type.includes('department')) {
            return 8;
        }
        if (
            type.includes('county')
            || type.includes('municipality')
            || type.includes('city')
            || type.includes('town')
            || type.includes('village')
        ) {
            return 11;
        }
        return 14;
    };

    const buildResultRows = (items) => {
        if (!items.length) {
            searchResults.innerHTML = `<div class="px-3 py-2 text-sm text-gray-500">${t.noResults}</div>`;
            searchResults.classList.remove('hidden');
            return;
        }

        const rows = items
            .map((item, index) => {
                const label = item.display_name || item.name || '';
                const type = item.type || item.addresstype || '';
                return `
                    <button
                        type="button"
                        class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm text-gray-700 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                        data-map-result-index="${index}"
                    >
                        <span class="block font-medium">${label}</span>
                        <span class="block text-xs text-gray-500">${type}</span>
                    </button>
                `;
            })
            .join('');

        searchResults.innerHTML = rows;
        searchResults.classList.remove('hidden');

        Array.from(searchResults.querySelectorAll('[data-map-result-index]')).forEach((button) => {
            button.addEventListener('click', () => {
                const index = Number.parseInt(button.dataset.mapResultIndex || '', 10);
                const item = items[index];
                if (!item) {
                    return;
                }
                const lat = Number.parseFloat(item.lat);
                const lng = Number.parseFloat(item.lon);
                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    return;
                }
                setSelectedLocation(lat, lng, guessZoomLevel(item));
                searchInput.value = item.display_name || '';
                hideSearchResults();
            });
        });
    };

    const searchByText = async (query) => {
        const text = query.trim();
        if (text.length < 2) {
            hideSearchResults();
            return;
        }

        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('q', `${text}, Colombia`);
        url.searchParams.set('countrycodes', 'co');
        url.searchParams.set('addressdetails', '1');
        url.searchParams.set('limit', '8');

        let items = [];
        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'Accept-Language': locale,
                },
                signal: searchAbortController.signal,
            });
            if (!response.ok) {
                hideSearchResults();
                return;
            }
            items = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') {
                hideSearchResults();
            }
            return;
        }

        buildResultRows(Array.isArray(items) ? items : []);
    };

    const openModal = (form) => {
        activeForm = form;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        selectedLatLng = null;

        if (acceptButton) {
            acceptButton.disabled = true;
        }
        if (errorMessage) {
            errorMessage.textContent = '';
            errorMessage.classList.add('hidden');
        }
        searchInput.value = '';
        hideSearchResults();

        const { latInput, lngInput } = resolveInputs();
        setNotice(activeForm, '');

        if (!mapInstance) {
            mapInstance = L.map(mapElement).setView([4.5709, -74.2973], 6);

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

            hybridBase.addTo(mapInstance);

            L.control
                .layers(
                    {
                        [t.layerHybrid]: hybridBase,
                        [t.layerStreets]: osmStreets,
                    },
                    {},
                    { position: 'topright', collapsed: false },
                )
                .addTo(mapInstance);

            mapInstance.on('click', (event) => {
                const { lat, lng } = event.latlng;
                setSelectedLocation(lat, lng, 14);
            });
        }

        if (latInput?.value && lngInput?.value) {
            const lat = Number.parseFloat(latInput.value);
            const lng = Number.parseFloat(lngInput.value);
            if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                setSelectedLocation(lat, lng, 14);
            }
        }

        setTimeout(() => {
            mapInstance.invalidateSize();
        }, 200);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        hideSearchResults();
    };

    const reverseGeocode = async (lat, lng) => {
        const backendUrl = new URL('/geocode/reverse', window.location.origin);
        backendUrl.searchParams.set('lat', lat);
        backendUrl.searchParams.set('lng', lng);
        try {
            const response = await fetch(backendUrl.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });
            if (response.ok) {
                return response.json();
            }
        } catch (error) {
            // Fallback below
        }

        const publicUrl = new URL('https://nominatim.openstreetmap.org/reverse');
        publicUrl.searchParams.set('format', 'jsonv2');
        publicUrl.searchParams.set('lat', lat);
        publicUrl.searchParams.set('lon', lng);
        publicUrl.searchParams.set('addressdetails', '1');
        publicUrl.searchParams.set('countrycodes', 'co');
        const response = await fetch(publicUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'Accept-Language': locale,
            },
        });
        if (!response.ok) {
            throw new Error('reverse geocode failed');
        }
        return response.json();
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(trigger.closest('[data-location-form]'));
        });
    });

    searchInput.addEventListener('input', (event) => {
        const query = event.target.value || '';
        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }
        searchDebounce = setTimeout(() => {
            searchByText(query);
        }, 300);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }
        const firstResult = searchResults.querySelector('[data-map-result-index="0"]');
        if (!firstResult) {
            return;
        }
        event.preventDefault();
        firstResult.click();
    });

    const shouldHandleLocationField = (field) => field.matches('[data-address-input], [data-neighborhood-input], [data-department-select], [data-municipality-select]');

    const handleLocationFieldChange = (field) => {
        const form = field.closest('[data-location-form]');
        if (!form || !shouldHandleLocationField(field)) {
            return;
        }

        const { coordsSourceInput } = resolveInputs(form);
        const isMapSelection = coordsSourceInput?.value === 'map';
        const isOnlyTextAdjustment = field.matches('[data-address-input], [data-neighborhood-input]');

        if (isMapSelection && isOnlyTextAdjustment) {
            setNotice(form, '');
            return;
        }

        clearCoordinates(form);
        setNotice(form, '');
        scheduleManualGeocode(form);
    };

    document.addEventListener('input', (event) => {
        handleLocationFieldChange(event.target);
    });

    document.addEventListener('change', (event) => {
        handleLocationFieldChange(event.target);
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal();
        });
    });

    if (acceptButton) {
        acceptButton.addEventListener('click', async (event) => {
            event.preventDefault();
            if (!selectedLatLng) {
                return;
            }

            const { addressInput, neighborhoodInput, departmentSelect, municipalitySelect, coordsSourceInput } = resolveInputs();
            const originalText = acceptButton.textContent;
            acceptButton.textContent = t.searching;
            acceptButton.disabled = true;

            if (errorMessage) {
                errorMessage.textContent = '';
                errorMessage.classList.add('hidden');
            }

            try {
                const data = await reverseGeocode(selectedLatLng.lat, selectedLatLng.lng);
                const address = data?.address ?? {};
                const road = address.road || address.pedestrian || address.path || address.cycleway || '';
                const number = address.house_number ? ` ${address.house_number}` : '';
                const addressValue = road ? `${road}${number}` : (data?.display_name ?? '');

                const municipality =
                    address.city
                    || address.town
                    || address.village
                    || address.municipality
                    || address.city_district
                    || address.suburb
                    || address.county
                    || '';
                const neighborhood =
                    address.neighbourhood
                    || address.suburb
                    || address.city_district
                    || address.quarter
                    || address.borough
                    || '';
                const department = address.state || address.region || address.state_district || '';
                const normalizedDepartment = normalizeDepartmentName(department);
                const normalizedMunicipality = normalizeMunicipalityName(municipality);

                if (addressInput && addressValue) {
                    addressInput.value = addressValue;
                }
                if (neighborhoodInput && neighborhood) {
                    neighborhoodInput.value = neighborhood;
                }

                if (departmentSelect && normalizedDepartment) {
                    if (!selectByNormalizedMatch(departmentSelect, normalizedDepartment)) {
                        const option = buildOption(normalizedDepartment, normalizedDepartment);
                        departmentSelect.appendChild(option);
                        departmentSelect.value = normalizedDepartment;
                    }
                    const departmentValue = departmentSelect.value || normalizedDepartment;
                    populateMunicipalities(municipalitySelect, departmentValue, '');
                }

                if (municipalitySelect && normalizedMunicipality) {
                    if (!selectByNormalizedMatch(municipalitySelect, normalizedMunicipality)) {
                        const option = buildOption(normalizedMunicipality, normalizedMunicipality);
                        municipalitySelect.appendChild(option);
                        municipalitySelect.value = normalizedMunicipality;
                    }
                }

                if (coordsSourceInput) {
                    coordsSourceInput.value = 'map';
                }
                setNotice(activeForm, '');

                if (!addressValue || !normalizedDepartment || !normalizedMunicipality) {
                    if (errorMessage) {
                        errorMessage.textContent = t.incompleteAddress;
                        errorMessage.classList.remove('hidden');
                    }
                } else {
                    closeModal();
                }
            } catch (error) {
                if (errorMessage) {
                    errorMessage.textContent = t.geocodeFailed;
                    errorMessage.classList.remove('hidden');
                }
            } finally {
                acceptButton.textContent = originalText;
                acceptButton.disabled = false;
            }
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
            return;
        }
        if (!searchWrapper.contains(event.target)) {
            hideSearchResults();
        }
    });
};

const init = () => {
    initLocationSelects();
    initMapPicker();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
