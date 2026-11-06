import React from 'react';
import {
  AbsoluteFill,
  Easing,
  Img,
  interpolate,
  Sequence,
  spring,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {
  ArrowRight,
  Bell,
  Box,
  Check,
  ChevronRight,
  CircleCheck,
  CreditCard,
  Cuboid,
  Heart,
  Home,
  MapPin,
  PackageCheck,
  Rotate3D,
  Search,
  ShieldCheck,
  ShoppingBag,
  Sparkles,
  Star,
  Store,
  Truck,
  UserRound,
  Zap,
} from 'lucide-react';
import './vertical-promo.css';

const logo = staticFile('assets/furnisync-logo.png');
const clamp = {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'} as const;

const fade = (frame: number, duration: number) =>
  interpolate(frame, [0, 14, duration - 14, duration], [0, 1, 1, 0], clamp);

const pop = (frame: number, fps: number, delay = 0, distance = 70) => {
  const value = spring({frame: frame - delay, fps, config: {damping: 17, stiffness: 110, mass: 0.78}});
  return {
    opacity: value,
    transform: `translateY(${(1 - value) * distance}px) scale(${0.93 + value * 0.07})`,
  };
};

const VLogo: React.FC<{small?: boolean}> = ({small = false}) => (
  <div className={`vp-logo ${small ? 'small' : ''}`}><Img src={logo}/></div>
);

const Atmosphere: React.FC<{dark?: boolean}> = ({dark = false}) => {
  const frame = useCurrentFrame();
  const shift = Math.sin(frame / 38) * 30;
  return (
    <div className={`vp-atmosphere ${dark ? 'dark' : ''}`}>
      <div className="vp-glow a" style={{transform: `translate(${shift}px,${shift * 0.35}px)`}}/>
      <div className="vp-glow b" style={{transform: `translate(${-shift}px,${-shift * 0.3}px)`}}/>
      <div className="vp-grid"/>
      <div className="vp-noise"/>
    </div>
  );
};

const SceneTitle: React.FC<{
  frame: number;
  index: string;
  eyebrow: string;
  title: React.ReactNode;
  body: string;
  dark?: boolean;
}> = ({frame, index, eyebrow, title, body, dark = false}) => {
  const {fps} = useVideoConfig();
  return (
    <div className={`vp-scene-title ${dark ? 'dark' : ''}`}>
      <div className="vp-kicker" style={pop(frame, fps)}><span>{index}</span>{eyebrow}</div>
      <h2 style={pop(frame, fps, 5)}>{title}</h2>
      <p style={pop(frame, fps, 10)}>{body}</p>
    </div>
  );
};

const Cabinet: React.FC<{rotate?: number; scale?: number}> = ({rotate = 0, scale = 1}) => (
  <div className="vp-cabinet-wrap" style={{transform: `scale(${scale}) rotateY(${rotate}deg)`}}>
    <div className="vp-cabinet-top"/>
    <div className="vp-cabinet-side"/>
    <div className="vp-cabinet-front">
      {[0, 1, 2, 3, 4].map((drawer) => <div className="vp-drawer" key={drawer}><i/></div>)}
    </div>
    <div className="vp-cabinet-leg left"/><div className="vp-cabinet-leg right"/>
  </div>
);

const Sofa: React.FC<{color?: string}> = ({color = '#77a8a5'}) => (
  <div className="vp-sofa" style={{'--sofa': color} as React.CSSProperties}>
    <div className="vp-sofa-back"/><div className="vp-sofa-arm left"/><div className="vp-sofa-arm right"/>
    <div className="vp-sofa-seat"><i/><i/></div><span className="leg l"/><span className="leg r"/>
  </div>
);

const Chair: React.FC = () => (
  <div className="vp-chair"><div className="vp-chair-back"/><div className="vp-chair-seat"/><i className="l"/><i className="r"/></div>
);

const Nav: React.FC<{cartCount?: number}> = ({cartCount = 0}) => (
  <div className="vp-nav"><VLogo small/><div className="vp-nav-actions"><Heart/><div className="vp-cart-icon"><ShoppingBag/>{cartCount > 0 && <b>{cartCount}</b>}</div><Bell/><UserRound/></div></div>
);

const BottomNav: React.FC<{active: 'home' | 'search' | 'cart' | 'account'}> = ({active}) => (
  <div className="vp-bottom-nav">
    {[[Home, 'home'], [Search, 'search'], [ShoppingBag, 'cart'], [UserRound, 'account']].map(([Icon, name]) => {
      const I = Icon as React.ComponentType<{size?: number}>;
      return <div className={active === name ? 'active' : ''} key={name as string}><I size={27}/><span>{name as string}</span></div>;
    })}
  </div>
);

const Opening: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const orbit = interpolate(frame, [0, durationInFrames], [-18, 30], clamp);
  return (
    <AbsoluteFill className="vp-scene vp-opening" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere dark/>
      <div className="vp-orbit one" style={{transform: `translate(-50%,-50%) rotate(${orbit}deg)`}}><div><Search/></div></div>
      <div className="vp-orbit two" style={{transform: `translate(-50%,-50%) rotate(${-orbit * 1.25}deg)`}}><div><ShoppingBag/></div></div>
      <div className="vp-opening-center">
        <div style={pop(frame, fps)}><VLogo/></div>
        <div className="vp-opening-pill" style={pop(frame, fps, 8)}><Sparkles/> ECOMMERCE, REIMAGINED</div>
        <h1 style={pop(frame, fps, 14)}>Your furniture journey.<br/><span>Now in motion.</span></h1>
        <p style={pop(frame, fps, 22)}>From discovery to delivery, every interaction moves beautifully with you.</p>
        <div className="vp-opening-cta" style={pop(frame, fps, 30)}>Explore the experience <ArrowRight/></div>
      </div>
      <div className="vp-opening-progress"><i style={{transform: `scaleX(${interpolate(frame, [0, durationInFrames], [0, 1], clamp)})`}}/></div>
    </AbsoluteFill>
  );
};

const Discover: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const typed = 'Search furniture'.slice(0, Math.floor(interpolate(frame, [24, 80], [0, 16], clamp)));
  const scroll = interpolate(frame, [90, 245], [0, -235], {...clamp, easing: Easing.inOut(Easing.cubic)});
  const cards = [
    {name: 'Chesnut Cabinet', price: '₱23,899', visual: <Cabinet scale={0.58}/>, badge: 'TOP 1'},
    {name: 'Nordic Sofa', price: '₱37,999', visual: <Sofa/>, badge: 'TOP 2'},
    {name: 'Accent Chair', price: '₱8,799', visual: <Chair/>, badge: 'NEW'},
  ];
  return (
    <AbsoluteFill className="vp-scene vp-discover" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere/>
      <SceneTitle frame={frame} index="01" eyebrow="DISCOVER" title={<>Find furniture that<br/><span>feels made for you.</span></>} body="Search, browse, and explore top pieces from local stores."/>
      <div className="vp-app-frame" style={pop(frame, fps, 15)}>
        <Nav/>
        <div className="vp-search"><Search/><span>{typed}</span><i/></div>
        <div className="vp-tabs"><b>Products</b><span>Rooms</span><span>Stores</span></div>
        <div className="vp-feed" style={{transform: `translateY(${scroll}px)`}}>
          <div className="vp-feed-heading"><div><h3>Top-selling furniture</h3><p>Popular pieces available this month.</p></div><ChevronRight/></div>
          <div className="vp-product-list">
            {cards.map((card, index) => <div className="vp-product-card" key={card.name} style={pop(frame, fps, 36 + index * 9)}>
              <div className="vp-product-art">{card.visual}<em>{card.badge}</em><button><Heart size={18}/></button></div>
              <small>FurniSync Select</small><b>{card.name}</b><div><span><Star size={14} fill="#f59e0b"/>4.{9-index}</span><strong>{card.price}</strong></div>
            </div>)}
          </div>
        </div>
        <BottomNav active="home"/>
      </div>
    </AbsoluteFill>
  );
};

const Product3D: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const rotation = interpolate(frame, [60, 240], [-24, 28], {...clamp, easing: Easing.inOut(Easing.cubic)});
  const active3d = frame > 65;
  const pulse = 1 + Math.sin(frame / 8) * 0.035;
  return (
    <AbsoluteFill className="vp-scene vp-product" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere dark/>
      <SceneTitle dark frame={frame} index="02" eyebrow="EXPLORE IN 3D" title={<>See the details.<br/><span>Feel the difference.</span></>} body="Rotate, inspect, and understand every angle before you buy."/>
      <div className="vp-product-ui" style={pop(frame, fps, 15)}>
        <Nav/>
        <div className="vp-viewer-head"><span className="vp-new">NEW</span><button className={active3d ? 'active' : ''} style={{transform: `scale(${pulse})`}}><Rotate3D/>{active3d ? '3D View' : 'View in 3D'}</button></div>
        <div className={`vp-viewer ${active3d ? 'active' : ''}`}>
          <div className="vp-viewer-grid"/>
          <Cabinet rotate={rotation} scale={1.12}/>
          <div className="vp-rotate-hint"><Rotate3D/> Drag to rotate</div>
          <div className="vp-viewer-shadow"/>
        </div>
        <div className="vp-product-meta"><div className="vp-rating"><Star fill="#f59e0b"/>4.9 <small>128 reviews</small></div><h3>Chesnut Cabinet</h3><p>Novo · Minimalist · Drawers & Chests</p><strong>₱23,899.00</strong></div>
        <div className="vp-buy-row"><button className="heart"><Heart/></button><button className="buy"><ShoppingBag/> Add to cart</button></div>
      </div>
      <div className="vp-gesture" style={pop(frame, fps, 88)}><span>↔</span><b>MOVE IT</b><small>Interactive product viewing</small></div>
    </AbsoluteFill>
  );
};

const Cart: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const count = Math.round(interpolate(frame, [80, 122], [1, 2], clamp));
  const total = interpolate(frame, [80, 150], [53898, 91887], clamp);
  return (
    <AbsoluteFill className="vp-scene vp-cart" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere/>
      <SceneTitle frame={frame} index="03" eyebrow="YOUR CART" title={<>Build your room.<br/><span>One piece at a time.</span></>} body="Review selections, adjust quantities, and stay in control."/>
      <div className="vp-cart-ui" style={pop(frame, fps, 16)}>
        <Nav cartCount={2}/><h3>My Cart</h3><div className="vp-store-label"><Store/><span><b>Gray Area Furnitures</b><small>2 products</small></span><button>Select all</button></div>
        <div className="vp-cart-items">
          <div className="vp-cart-item" style={pop(frame, fps, 38)}><div className="art"><Chair/></div><div className="info"><b>Soviet Arm Rest</b><small>Standard</small><strong>₱15,899.00</strong><div className="counter">− <span>1</span> +</div></div><Heart/></div>
          <div className="vp-cart-item" style={pop(frame, fps, 50)}><div className="art"><Sofa/></div><div className="info"><b>Modern Teal 3-Seater</b><small>Standard</small><strong>₱37,999.00</strong><div className="counter">− <span>{count}</span> +</div></div><Heart/></div>
        </div>
        <div className="vp-order-summary"><span>Order total</span><b>₱{Math.round(total).toLocaleString('en-US')}.00</b><button>Checkout <ArrowRight/></button></div>
        <BottomNav active="cart"/>
      </div>
      <div className="vp-added-toast" style={pop(frame, fps, 105)}><CircleCheck/><span><b>Cart updated</b><small>Quantity and total synchronized</small></span></div>
    </AbsoluteFill>
  );
};

const Checkout: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const selected = frame > 76;
  const confirmed = frame > 178;
  return (
    <AbsoluteFill className="vp-scene vp-checkout" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere dark/>
      <SceneTitle dark frame={frame} index="04" eyebrow="CHECKOUT" title={<>Simple choices.<br/><span>Secure decisions.</span></>} body="Choose fulfillment, review costs, and place your order with confidence."/>
      <div className="vp-checkout-ui" style={pop(frame, fps, 16)}>
        <VLogo small/><div className="vp-checkout-total">Order total <b>₱28,174.65</b></div><h3>Review and place your order</h3><p>Confirm fulfillment and payment.</p>
        <div className="vp-stepper"><div className="done"><Check/> Address</div><i/><div className="active">2 <span>Service</span></div><i/><div>3 <span>Payment</span></div></div>
        <div className="vp-service-card"><h4>How would you like to receive your order?</h4><p>Choose how this order gets to you.</p>
          <div className={!selected ? 'selected' : ''}><span><b>Store pickup</b><small>Collect after confirmation.</small></span><strong>Free</strong></div>
          <div className={selected ? 'selected' : ''}><span><b>Truck delivery</b><small>Delivered to your address.</small></span><strong>₱7,382.65</strong>{selected && <Check/>}</div>
        </div>
        <button className="vp-continue">Continue to payment <ArrowRight/></button>
        {confirmed && <div className="vp-confirm-overlay"><div><Check/></div><h3>Ready for payment</h3><p>Delivery service confirmed.</p></div>}
      </div>
      <div className="vp-secure-badge" style={pop(frame, fps, 55)}><ShieldCheck/><span><b>Protected checkout</b><small>Clear totals. No surprises.</small></span></div>
    </AbsoluteFill>
  );
};

const routePoint = (progress: number) => {
  const points = [{x: 155, y: 700}, {x: 295, y: 595}, {x: 185, y: 475}, {x: 335, y: 360}, {x: 235, y: 235}];
  const scaled = Math.min(progress, 0.999) * (points.length - 1);
  const index = Math.floor(scaled);
  const local = scaled - index;
  return {
    x: points[index].x + (points[index + 1].x - points[index].x) * local,
    y: points[index].y + (points[index + 1].y - points[index].y) * local,
  };
};

const Tracking: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const progress = interpolate(frame, [55, 250], [0, 0.99], {...clamp, easing: Easing.inOut(Easing.cubic)});
  const point = routePoint(progress);
  const dash = 980 * (1 - progress);
  return (
    <AbsoluteFill className="vp-scene vp-tracking" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere/>
      <SceneTitle frame={frame} index="05" eyebrow="LIVE TRACKING" title={<>Know where it is.<br/><span>Know when it arrives.</span></>} body="Follow every delivery milestone with live route updates."/>
      <div className="vp-track-ui" style={pop(frame, fps, 16)}>
        <Nav/><div className="vp-track-head"><div><span className="live"><i/> LIVE</span><h3>Your furniture is on the way</h3><p>Saturn Street, Dasmariñas, Cavite</p></div><Truck/></div>
        <div className="vp-map">
          <div className="vp-map-roads"/><span className="place one">Silang</span><span className="place two">Dasmariñas</span><span className="place three">Tagaytay</span>
          <svg viewBox="0 0 500 850" preserveAspectRatio="none"><path className="route-shadow" d="M155 700 C230 650 315 625 295 595 C260 545 175 535 185 475 C195 420 350 410 335 360 C320 305 220 300 235 235"/><path className="route" style={{strokeDashoffset: dash}} d="M155 700 C230 650 315 625 295 595 C260 545 175 535 185 475 C195 420 350 410 335 360 C320 305 220 300 235 235"/></svg>
          <div className="vp-origin"><Home/></div><div className="vp-destination"><MapPin fill="#ff6815"/></div>
          <div className="vp-moving-truck" style={{left: point.x, top: point.y}}><Truck/></div>
        </div>
        <div className="vp-delivery-status"><div><Box/><span><small>STATUS</small><b>{progress < .55 ? 'Dispatched' : 'On the way'}</b></span></div><div><PackageCheck/><span><small>ARRIVAL</small><b>Today · 2–5 PM</b></span></div></div>
        <BottomNav active="home"/>
      </div>
    </AbsoluteFill>
  );
};

const Closing: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const features = [[Search, 'Discover'], [Cuboid, 'Explore in 3D'], [ShoppingBag, 'Shop'], [Truck, 'Track']];
  return (
    <AbsoluteFill className="vp-scene vp-closing" style={{opacity: fade(frame, durationInFrames)}}>
      <Atmosphere dark/>
      <div className="vp-closing-content">
        <div style={pop(frame, fps)}><VLogo/></div>
        <h2 style={pop(frame, fps, 7)}>Furniture shopping,<br/><span>beautifully connected.</span></h2>
        <p style={pop(frame, fps, 14)}>One seamless experience—from the first search to your front door.</p>
        <div className="vp-feature-row">
          {features.map(([Icon, text], index) => {const I = Icon as React.ComponentType<{size?: number}>; return <div key={text as string} style={pop(frame, fps, 24 + index * 6)}><I/><b>{text as string}</b></div>;})}
        </div>
        <div className="vp-final-cta" style={pop(frame, fps, 48)}>Experience FurniSync <ArrowRight/></div>
      </div>
      <div className="vp-closing-line"><i style={{transform: `scaleX(${interpolate(frame, [0, durationInFrames], [0, 1], clamp)})`}}/></div>
    </AbsoluteFill>
  );
};

export const FurniSyncVerticalPromo: React.FC = () => (
  <AbsoluteFill className="vp-root">
    <Sequence durationInFrames={180}><Opening/></Sequence>
    <Sequence from={180} durationInFrames={300}><Discover/></Sequence>
    <Sequence from={480} durationInFrames={300}><Product3D/></Sequence>
    <Sequence from={780} durationInFrames={270}><Cart/></Sequence>
    <Sequence from={1050} durationInFrames={270}><Checkout/></Sequence>
    <Sequence from={1320} durationInFrames={300}><Tracking/></Sequence>
    <Sequence from={1620} durationInFrames={180}><Closing/></Sequence>
  </AbsoluteFill>
);
