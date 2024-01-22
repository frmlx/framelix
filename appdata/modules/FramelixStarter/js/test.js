class FramelixStarterTest {
  static init () {
    console.log('Yea, test initialized')
  }
}

FramelixInit.late.push(FramelixStarterTest.init)