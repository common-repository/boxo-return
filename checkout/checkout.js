import {parsePostalCode} from '../utils/utils.js'

const serverData = document.getElementById('boxo-data')
if (!(serverData instanceof HTMLDivElement)) {
  throw new Error('[Boxo Return] Could not get server data')
}

const serviceAvailableUrl = serverData.getAttribute('data-service-available-url')
if (!serviceAvailableUrl) {
  throw new Error('[Boxo Return] Could not get service available URL')
}

const shopCountry = serverData.getAttribute('data-shop-country')

/**
 * The state of the Boxo container is tracked so that it can be persisted through partial reloads triggered by WooCommerce.
 * For example, when the user changes the postal code, the order review section is reloaded (and the update_checkout event is dispatched) and the state is reapplied.
 * @type {{containerVisible: boolean; selection: string | null}}
 */
const state = {
  containerVisible: false,
  selection: null
}

// Showing and hiding works differently for the Boxo container and the inputs:
// - Container: because this is a table row, it needs to stay in the DOM, and is shown/hidden using CSS.
// - Inputs: because these are registered in the form, they need to be added to or removed from the DOM completely.
const showBoxoContainer = () => {
  const container = document.getElementById('boxo_container')
  if (!(container instanceof HTMLTableRowElement)) {
    throw Error('[Boxo Return] Could not get container')
  }

  if (container.hasAttribute('data-visible')) {
    return
  }

  // Show the container
  state.containerVisible = true
  container.setAttribute('data-visible', '')

  // Add the inputs
  const inputsContainer = document.getElementById('boxo_inputs_container')
  if (!(inputsContainer instanceof HTMLDivElement)) {
    throw new Error('[Boxo Return] Could not get inputs container')
  }

  const inputsTemplate = document.getElementById('boxo_inputs_template')
  if (!(inputsTemplate instanceof HTMLTemplateElement)) {
    throw new Error('[Boxo Return] Could not get inputs template')
  }

  const clone = inputsTemplate.content.cloneNode(true)
  inputsContainer.appendChild(clone)
}

const hideBoxoContainer = () => {
  const container = document.getElementById('boxo_container')
  if (!(container instanceof HTMLTableRowElement)) {
    throw Error('[Boxo Return] Could not get container')
  }

  // Hide the container
  state.containerVisible = false
  container.removeAttribute('data-visible')

  const inputsContainer = document.getElementById('boxo_inputs_container')
  if (!(inputsContainer instanceof HTMLDivElement)) {
    throw new Error('[Boxo Return] Could not get inputs container')
  }

  // Remove the inputs
  inputsContainer.innerHTML = ''
}

const applyState = () => {
  if (state.containerVisible) {
    showBoxoContainer()
  }

  if (!state.selection) {
    return
  }

  const selectedInput = document.querySelector(`[name=boxo_packaging][value=${state.selection}]`)
  if (!(selectedInput instanceof HTMLInputElement)) {
    throw new Error('[Boxo Return] Could not find selected input')
  }
  selectedInput.checked = true
}

/**
 * Check whether Boxo is available for a postal code.
 * @param {string} postalCode
 */
const isBoxoAvailable = async (postalCode) => {
  try {
    const url = new URL(serviceAvailableUrl)
    url.searchParams.set('postal_code', postalCode)

    const res = await fetch(url.toString())
    if (!res.ok) {
      console.error(`[Boxo Return] ${res.status} ${res.statusText}`)
      return false
    }

    const available = (await res.json()).available
    if (typeof available !== 'boolean') {
      console.error('[Boxo Return] Unexpected response')
      return false
    }

    return available
  } catch (err) {
    console.error('[Boxo Return]', err)
    return false
  }
}

/**
 * Check whether Boxo can be used and show or hide the packaging input accordingly.
 */
const handleChange = async () => {
  hideBoxoContainer()

  // If shipping address is different from billing address, use shipping address.
  // Otherwise, use billing address.
  const shippingAddressCheckbox = document.getElementById('ship-to-different-address-checkbox')
  const hasShippingAddress =
    shippingAddressCheckbox instanceof HTMLInputElement && shippingAddressCheckbox.checked

  const countryInput = hasShippingAddress
    ? document.getElementById('shipping_country')
    : document.getElementById('billing_country')

  // Shipping/billing countries can come from a <select> (multiple countries), a hidden <input> (single country),
  // or can be removed from the page entirely, in which case the shop country is used.
  const country =
    countryInput instanceof HTMLSelectElement || countryInput instanceof HTMLInputElement
      ? countryInput.value
      : shopCountry
  if (country !== 'NL') {
    return
  }

  const postalCodeInput = hasShippingAddress
    ? document.getElementById('shipping_postcode')
    : document.getElementById('billing_postcode')
  if (!(postalCodeInput instanceof HTMLInputElement)) {
    console.error('[Boxo Return] Could not get postal code input')
    return
  }

  const postalCode = parsePostalCode(postalCodeInput.value)
  if (!postalCode) {
    return
  }

  if (await isBoxoAvailable(postalCode)) {
    showBoxoContainer()
  }
}

const init = () => {
  handleChange()

  // Must use jQuery for event handlers because propagation of certain events appears to be blocked.
  jQuery(function ($) {
    $(document.body).on(
      'change',
      '#billing_country, #billing_postcode, #shipping_country, #shipping_postcode, #ship-to-different-address-checkbox',
      () => {
        handleChange()
        document.body.dispatchEvent(new Event('update_checkout'))
      }
    )

    $(document.body).on('change', '[name=boxo_packaging]', (e) => {
      state.selection = e.target.value
      document.body.dispatchEvent(new Event('update_checkout'))
    })

    // Order review is reloaded by WooCommerce on certain events, eg. when the postal code is changed.
    // Because this resets the boxo container in the order review, we need to reapply the previous state.
    $(document.body).on('updated_checkout', applyState)
  })
  console.info('[Boxo Return] Ready')
}

if (document.readyState !== 'loading') {
  init()
} else {
  document.addEventListener('DOMContentLoaded', init)
}
