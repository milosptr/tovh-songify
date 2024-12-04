// User account navigation
const accountLinks = document.getElementById('block-songify-accountlinks') || document.getElementById('block-songify-useraccountmenu')
const accountLinkButton = accountLinks.querySelector('a[href]')

if(accountLinks) {
  accountLinkButton.addEventListener('click', (event) => {
    event.preventDefault()
    accountLinks.classList.toggle('open')
  })
}
